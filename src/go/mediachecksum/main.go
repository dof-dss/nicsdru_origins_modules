/*
  Go script to generate SHA256 checksums for media entities of specific bundle types
  (defaults: image, document) and write them to the duplicates_checksum column of
  media_field_data.

  Public and private file paths are read from the Drupal system.file configuration
  stored in the database. Override them with --public-dir / --private-dir if needed.

  To compile for Linux run: GOOS=linux GOARCH=amd64 go build -o dof-dss-mediachecksum .
*/

package main

import (
	"crypto/sha256"
	"database/sql"
	"encoding/base64"
	"encoding/json"
	"errors"
	"flag"
	"fmt"
	"io"
	"log"
	"net/url"
	"os"
	"path/filepath"
	"strconv"
	"strings"
	"time"

	_ "github.com/go-sql-driver/mysql"
)

const version = "1.0.0"

type Config struct {
	DSN        string
	WebRoot    string
	PublicDir  string
	PrivateDir string
	Bundles    []string
	BatchSize  int
	DryRun     bool
	Force      bool
	Verbose    bool
}

type Stats struct {
	Processed    int
	Updated      int
	Skipped      int
	HashErrors   int
	FileNotFound int
	URIErrors    int
}

// BundleTable maps a media bundle field table to its file target ID column.
type BundleTable struct {
	Table  string
	Column string
}

// Ordered list of bundle field tables to try when resolving a file FID.
var bundleTables = []BundleTable{
	{Table: "media__field_media_image", Column: "field_media_image_target_id"},
	{Table: "media__field_media_file", Column: "field_media_file_target_id"},
	{Table: "media__field_media_file_1", Column: "field_media_file_1_target_id"},
	{Table: "media__field_media_document", Column: "field_media_document_target_id"},
}

var errNoPlatformRelationships = errors.New("PLATFORM_RELATIONSHIPS not set")

// platformRelationship holds connection details from Platform.sh PLATFORM_RELATIONSHIPS.
type platformRelationship struct {
	Scheme   string          `json:"scheme"`
	Host     string          `json:"host"`
	Port     json.RawMessage `json:"port"`
	Username string          `json:"username"`
	Password string          `json:"password"`
	Path     string          `json:"path"`
	DbName   string          `json:"dbname"`
	Database string          `json:"database"`
	Uri      string          `json:"uri"`
}

// ---- DSN auto-detection -------------------------------------------------------

func autoPopulateDSN() (string, error) {
	if dsn, err := autoPopulateDSNFromPlatformRelationships(); err == nil {
		return dsn, nil
	} else if err != nil && !errors.Is(err, errNoPlatformRelationships) {
		return "", err
	}
	return autoPopulateDSNFromStandardEnv()
}

func autoPopulateDSNFromPlatformRelationships() (string, error) {
	rels := os.Getenv("PLATFORM_RELATIONSHIPS")
	if rels == "" {
		return "", errNoPlatformRelationships
	}
	decoded, err := base64.StdEncoding.DecodeString(rels)
	if err != nil {
		return "", fmt.Errorf("failed to decode PLATFORM_RELATIONSHIPS: %w", err)
	}
	var relationships map[string][]platformRelationship
	if err := json.Unmarshal(decoded, &relationships); err != nil {
		return "", fmt.Errorf("failed to parse PLATFORM_RELATIONSHIPS: %w", err)
	}
	for group, items := range relationships {
		for _, rel := range items {
			if !isMySQLRelationship(group, rel) {
				continue
			}
			if dsn, err := buildPlatformRelationshipDSN(rel); err == nil {
				return dsn, nil
			}
		}
	}
	return "", fmt.Errorf("no mysql relationship found in PLATFORM_RELATIONSHIPS")
}

func autoPopulateDSNFromStandardEnv() (string, error) {
	if rawURI := os.Getenv("DATABASE_URL"); rawURI != "" {
		if dsn, err := dsnFromURI(rawURI); err == nil {
			return dsn, nil
		}
	}
	host := getenvFirst("DB_HOST", "MYSQL_HOST")
	if host == "" {
		return "", errNoPlatformRelationships
	}
	port := getenvFirst("DB_PORT", "MYSQL_PORT")
	user := getenvFirst("DB_USER", "MYSQL_USER")
	password := getenvFirst("DB_PASSWORD", "MYSQL_PASSWORD")
	dbname := getenvFirst("DB_NAME", "MYSQL_DATABASE")
	if user == "" || port == "" || dbname == "" {
		return "", fmt.Errorf("incomplete DB_* environment variables for DSN auto-detection")
	}
	return fmt.Sprintf("%s:%s@tcp(%s:%s)/%s", user, password, host, port, dbname), nil
}

func isMySQLRelationship(group string, rel platformRelationship) bool {
	scheme := strings.ToLower(rel.Scheme)
	if scheme == "mysql" || scheme == "mariadb" {
		return true
	}
	if strings.Contains(strings.ToLower(group), "mysql") || strings.Contains(strings.ToLower(group), "database") {
		return true
	}
	if rel.Uri != "" {
		if u, err := url.Parse(rel.Uri); err == nil {
			scheme = strings.ToLower(u.Scheme)
			return scheme == "mysql" || scheme == "mariadb"
		}
	}
	return false
}

func buildPlatformRelationshipDSN(rel platformRelationship) (string, error) {
	if rel.Uri != "" {
		if dsn, err := dsnFromURI(rel.Uri); err == nil {
			return dsn, nil
		}
	}
	host := strings.TrimSpace(rel.Host)
	port := relationshipPort(rel.Port)
	user := strings.TrimSpace(rel.Username)
	password := rel.Password
	dbname := strings.TrimSpace(rel.DbName)
	if dbname == "" {
		dbname = strings.TrimSpace(rel.Database)
	}
	if dbname == "" {
		dbname = strings.TrimPrefix(strings.TrimSpace(rel.Path), "/")
	}
	if host == "" || port == "" || user == "" || dbname == "" {
		return "", fmt.Errorf("incomplete mysql relationship data")
	}
	return fmt.Sprintf("%s:%s@tcp(%s:%s)/%s", user, password, host, port, dbname), nil
}

func relationshipPort(raw json.RawMessage) string {
	if len(raw) == 0 {
		return ""
	}
	var s string
	if err := json.Unmarshal(raw, &s); err == nil {
		return s
	}
	var n int
	if err := json.Unmarshal(raw, &n); err == nil {
		return strconv.Itoa(n)
	}
	return ""
}

func dsnFromURI(rawURI string) (string, error) {
	u, err := url.Parse(rawURI)
	if err != nil {
		return "", err
	}
	scheme := strings.ToLower(u.Scheme)
	if scheme != "mysql" && scheme != "mariadb" {
		return "", fmt.Errorf("unsupported URI scheme %q", u.Scheme)
	}
	if u.User == nil {
		return "", fmt.Errorf("URI missing credentials")
	}
	username := u.User.Username()
	password, _ := u.User.Password()
	host := u.Hostname()
	port := u.Port()
	if host == "" || port == "" {
		return "", fmt.Errorf("URI missing host or port")
	}
	dbname := strings.TrimPrefix(u.Path, "/")
	if dbname == "" {
		return "", fmt.Errorf("URI missing database name")
	}
	return fmt.Sprintf("%s:%s@tcp(%s:%s)/%s", username, password, host, port, dbname), nil
}

func getenvFirst(keys ...string) string {
	for _, key := range keys {
		if v := os.Getenv(key); v != "" {
			return v
		}
	}
	return ""
}

// ---- PHP unserializer ---------------------------------------------------------
// Minimal implementation covering the types present in Drupal config data:
// null (N), bool (b), int (i), float (d), string (s), array (a).

type phpParser struct {
	data []byte
	pos  int
}

func phpUnserialize(data []byte) (interface{}, error) {
	p := &phpParser{data: data}
	v, err := p.parseValue()
	if err != nil {
		return nil, err
	}
	return v, nil
}

func (p *phpParser) parseValue() (interface{}, error) {
	if p.pos >= len(p.data) {
		return nil, fmt.Errorf("unexpected end of input at pos %d", p.pos)
	}
	switch p.data[p.pos] {
	case 'N': // N;
		p.pos++
		return nil, p.expect(';')
	case 'b': // b:0; or b:1;
		p.pos++
		if err := p.expect(':'); err != nil {
			return nil, err
		}
		ch := p.data[p.pos]
		p.pos++
		return ch == '1', p.expect(';')
	case 'i': // i:123;
		p.pos++
		if err := p.expect(':'); err != nil {
			return nil, err
		}
		raw, err := p.readUntil(';')
		if err != nil {
			return nil, err
		}
		return strconv.ParseInt(string(raw), 10, 64)
	case 'd': // d:1.23;
		p.pos++
		if err := p.expect(':'); err != nil {
			return nil, err
		}
		raw, err := p.readUntil(';')
		if err != nil {
			return nil, err
		}
		return strconv.ParseFloat(string(raw), 64)
	case 's': // s:N:"...";
		p.pos++
		if err := p.expect(':'); err != nil {
			return nil, err
		}
		lenRaw, err := p.readUntil(':')
		if err != nil {
			return nil, err
		}
		length, err := strconv.Atoi(string(lenRaw))
		if err != nil {
			return nil, fmt.Errorf("invalid string length: %w", err)
		}
		if err := p.expect('"'); err != nil {
			return nil, err
		}
		if p.pos+length > len(p.data) {
			return nil, fmt.Errorf("string of length %d extends beyond input", length)
		}
		str := string(p.data[p.pos : p.pos+length])
		p.pos += length
		if err := p.expect('"'); err != nil {
			return nil, err
		}
		return str, p.expect(';')
	case 'a': // a:N:{...}
		p.pos++
		if err := p.expect(':'); err != nil {
			return nil, err
		}
		countRaw, err := p.readUntil(':')
		if err != nil {
			return nil, err
		}
		count, err := strconv.Atoi(string(countRaw))
		if err != nil {
			return nil, fmt.Errorf("invalid array count: %w", err)
		}
		if err := p.expect('{'); err != nil {
			return nil, err
		}
		result := make(map[string]interface{}, count)
		for i := 0; i < count; i++ {
			key, err := p.parseValue()
			if err != nil {
				return nil, fmt.Errorf("array key %d: %w", i, err)
			}
			val, err := p.parseValue()
			if err != nil {
				return nil, fmt.Errorf("array value %d: %w", i, err)
			}
			result[fmt.Sprintf("%v", key)] = val
		}
		return result, p.expect('}')
	default:
		return nil, fmt.Errorf("unknown type %q at pos %d", p.data[p.pos], p.pos)
	}
}

func (p *phpParser) expect(c byte) error {
	if p.pos >= len(p.data) {
		return fmt.Errorf("expected %q but reached end of input at pos %d", c, p.pos)
	}
	if p.data[p.pos] != c {
		return fmt.Errorf("expected %q but got %q at pos %d", c, p.data[p.pos], p.pos)
	}
	p.pos++
	return nil
}

func (p *phpParser) readUntil(delim byte) ([]byte, error) {
	start := p.pos
	for p.pos < len(p.data) {
		if p.data[p.pos] == delim {
			result := p.data[start:p.pos]
			p.pos++
			return result, nil
		}
		p.pos++
	}
	return nil, fmt.Errorf("delimiter %q not found from pos %d", delim, start)
}

// ---- Drupal config reading ----------------------------------------------------

// readDrupalFilePaths queries system.file config and returns the public and private
// file stream paths as configured in Drupal.
func readDrupalFilePaths(db *sql.DB) (publicPath, privatePath string, err error) {
	var data []byte
	err = db.QueryRow(
		"SELECT data FROM config WHERE collection = '' AND name = 'system.file' LIMIT 1",
	).Scan(&data)
	if err != nil {
		return "", "", fmt.Errorf("query system.file config: %w", err)
	}

	parsed, err := phpUnserialize(data)
	if err != nil {
		return "", "", fmt.Errorf("parse system.file config: %w", err)
	}

	configMap, ok := parsed.(map[string]interface{})
	if !ok {
		return "", "", fmt.Errorf("unexpected system.file config format")
	}

	pathSection, ok := configMap["path"].(map[string]interface{})
	if !ok {
		return "", "", fmt.Errorf("'path' key not found in system.file config")
	}

	if pub, ok := pathSection["public"].(string); ok {
		publicPath = pub
	}
	if priv, ok := pathSection["private"].(string); ok {
		privatePath = priv
	}
	return publicPath, privatePath, nil
}

// resolveDrupalPath turns a possibly-relative Drupal file path into an absolute
// filesystem path. If the configured path is already absolute, it is returned
// as-is. Otherwise it is resolved relative to webRoot.
func resolveDrupalPath(webRoot, drupalPath string) (string, error) {
	if filepath.IsAbs(drupalPath) {
		return drupalPath, nil
	}
	if webRoot == "" {
		return "", fmt.Errorf(
			"path %q is relative but --web-root was not provided; use --web-root or --public-dir/--private-dir",
			drupalPath,
		)
	}
	return filepath.Join(webRoot, filepath.FromSlash(drupalPath)), nil
}

// ---- File hashing -------------------------------------------------------------

// hashFile computes the SHA256 hex digest of the file at path.
func hashFile(path string) (string, error) {
	f, err := os.Open(path)
	if err != nil {
		if os.IsNotExist(err) {
			return "", fmt.Errorf("file not found: %q", path)
		}
		return "", fmt.Errorf("open %q: %w", path, err)
	}
	defer f.Close()

	h := sha256.New()
	if _, err := io.Copy(h, f); err != nil {
		return "", fmt.Errorf("read %q: %w", path, err)
	}
	return fmt.Sprintf("%x", h.Sum(nil)), nil
}

// ---- URI → filesystem path ----------------------------------------------------

func uriToPath(cfg Config, uri string) (string, error) {
	switch {
	case strings.HasPrefix(uri, "public://"):
		return filepath.Join(cfg.PublicDir, filepath.FromSlash(strings.TrimPrefix(uri, "public://"))), nil
	case strings.HasPrefix(uri, "private://"):
		if cfg.PrivateDir == "" {
			return "", fmt.Errorf("private:// URI requires --private-dir to be set")
		}
		return filepath.Join(cfg.PrivateDir, filepath.FromSlash(strings.TrimPrefix(uri, "private://"))), nil
	default:
		if filepath.IsAbs(uri) {
			return uri, nil
		}
		return "", fmt.Errorf("unrecognised URI scheme: %q", uri)
	}
}

// ---- File FID resolution ------------------------------------------------------

// resolveFileURI walks bundle field tables to find the file FID for a media entity,
// then looks up the URI in file_managed.
// Only entity_id is used (not revision_id or langcode) because the non-revision field
// tables store only the current revision, and media files are rarely per-translation.
func resolveFileURI(db *sql.DB, mid int64) (string, error) {
	for _, ft := range bundleTables {
		query := fmt.Sprintf(
			"SELECT `%s` FROM `%s` WHERE entity_id = ? AND deleted = 0 LIMIT 1",
			ft.Column, ft.Table,
		)
		var fid int64
		err := db.QueryRow(query, mid).Scan(&fid)
		if err == sql.ErrNoRows || fid == 0 {
			continue
		}
		if err != nil {
			if strings.Contains(err.Error(), "doesn't exist") || strings.Contains(err.Error(), "1146") {
				continue
			}
			return "", fmt.Errorf("query %s mid=%d: %w", ft.Table, mid, err)
		}

		var uri string
		err = db.QueryRow("SELECT uri FROM file_managed WHERE fid = ?", fid).Scan(&uri)
		if err == sql.ErrNoRows {
			continue
		}
		if err != nil {
			return "", fmt.Errorf("file_managed lookup fid=%d: %w", fid, err)
		}
		return uri, nil
	}
	return "", fmt.Errorf("no file reference found for mid=%d", mid)
}

// ---- Database helpers ---------------------------------------------------------

func ensureChecksumColumn(db *sql.DB, dryRun bool) error {
	_, err := db.Exec("SELECT duplicates_checksum FROM media_field_data LIMIT 0")
	if err == nil {
		log.Println("duplicates_checksum column already exists")
		return nil
	}
	if !strings.Contains(err.Error(), "Unknown column") {
		return fmt.Errorf("probing duplicates_checksum column: %w", err)
	}
	if dryRun {
		log.Println("[dry-run] Would add duplicates_checksum VARCHAR(64) column")
		return nil
	}
	log.Println("Adding duplicates_checksum column to media_field_data …")
	_, err = db.Exec(`
		ALTER TABLE media_field_data
		ADD COLUMN duplicates_checksum VARCHAR(64) NULL DEFAULT NULL
	`)
	if err != nil {
		return fmt.Errorf("ALTER TABLE: %w", err)
	}
	log.Println("Checksum column added")
	return nil
}

func bundlePlaceholders(bundles []string) string {
	ph := make([]string, len(bundles))
	for i := range bundles {
		ph[i] = "?"
	}
	return strings.Join(ph, ", ")
}

func bundleArgs(bundles []string) []interface{} {
	args := make([]interface{}, len(bundles))
	for i, b := range bundles {
		args[i] = b
	}
	return args
}

func countRows(db *sql.DB, cfg Config) (int, error) {
	ph := bundlePlaceholders(cfg.Bundles)
	// Join with media to restrict to the current revision only, and filter to
	// default_langcode = 1 so we process one row per entity (not one per translation).
	query := fmt.Sprintf(`
		SELECT COUNT(*)
		FROM media_field_data mfd
		INNER JOIN media m ON m.mid = mfd.mid AND m.vid = mfd.vid
		WHERE mfd.bundle IN (%s)
		AND mfd.default_langcode = 1`, ph)
	if !cfg.Force {
		query += " AND mfd.duplicates_checksum IS NULL"
	}
	args := bundleArgs(cfg.Bundles)
	var count int
	err := db.QueryRow(query, args...).Scan(&count)
	return count, err
}

// ---- Core processing ----------------------------------------------------------

func processMedia(db *sql.DB, cfg Config) error {
	total, err := countRows(db, cfg)
	if err != nil {
		return err
	}
	if total == 0 {
		log.Println("No media rows to process (all may already have checksums)")
		return nil
	}
	log.Printf("Processing %d rows (batch size: %d, bundles: %s) …",
		total, cfg.BatchSize, strings.Join(cfg.Bundles, ", "))

	stats := &Stats{}
	// Cursor-based pagination: track the last mid processed so that rows updated
	// mid-run (checksum written, disappearing from the NULL filter) don't cause
	// subsequent OFFSET pages to skip unprocessed rows.
	var lastMid int64 = 0

	for {
		ph := bundlePlaceholders(cfg.Bundles)
		// Join with media so we only process the current revision of each entity,
		// and filter default_langcode = 1 so we get one row per entity.
		selectSQL := fmt.Sprintf(`
			SELECT mfd.mid, mfd.vid, mfd.langcode
			FROM media_field_data mfd
			INNER JOIN media m ON m.mid = mfd.mid AND m.vid = mfd.vid
			WHERE mfd.bundle IN (%s)
			AND mfd.default_langcode = 1
			AND mfd.mid > ?`, ph)
		if !cfg.Force {
			selectSQL += " AND mfd.duplicates_checksum IS NULL"
		}
		selectSQL += " ORDER BY mfd.mid LIMIT ?"

		args := append(bundleArgs(cfg.Bundles), lastMid, cfg.BatchSize)
		rows, err := db.Query(selectSQL, args...)
		if err != nil {
			return fmt.Errorf("query batch after mid=%d: %w", lastMid, err)
		}

		type pending struct {
			mid, vid int64
			langcode string
			checksum string
		}
		var batch []pending
		batchCount := 0

		for rows.Next() {
			var mid, vid int64
			var langcode string
			if err := rows.Scan(&mid, &vid, &langcode); err != nil {
				rows.Close()
				return fmt.Errorf("scan row: %w", err)
			}
			batchCount++
			lastMid = mid

			uri, err := resolveFileURI(db, mid)
			if err != nil {
				if cfg.Verbose {
					log.Printf("  [skip] mid=%d: %v", mid, err)
				}
				stats.URIErrors++
				stats.Skipped++
				continue
			}

			path, err := uriToPath(cfg, uri)
			if err != nil {
				if cfg.Verbose {
					log.Printf("  [skip] mid=%d uri=%q: %v", mid, uri, err)
				}
				stats.URIErrors++
				stats.Skipped++
				continue
			}

			absPath, err := filepath.Abs(path)
			if err != nil {
				if cfg.Verbose {
					log.Printf("  [skip] mid=%d path=%q: %v", mid, path, err)
				}
				stats.URIErrors++
				stats.Skipped++
				continue
			}

			checksum, err := hashFile(absPath)
			if err != nil {
				if strings.Contains(err.Error(), "file not found") {
					stats.FileNotFound++
				} else {
					stats.HashErrors++
				}
				if cfg.Verbose {
					log.Printf("  [skip] mid=%d path=%q: %v", mid, absPath, err)
				}
				stats.Skipped++
				continue
			}

			if cfg.Verbose {
				log.Printf("  mid=%-6d  %s  →  %s", mid, uri, checksum)
			}
			batch = append(batch, pending{mid: mid, vid: vid, langcode: langcode, checksum: checksum})
		}
		rows.Close()
		if err := rows.Err(); err != nil {
			return fmt.Errorf("row iteration: %w", err)
		}

		if !cfg.DryRun && len(batch) > 0 {
			tx, err := db.Begin()
			if err != nil {
				return fmt.Errorf("begin transaction: %w", err)
			}
			stmt, err := tx.Prepare(`
				UPDATE media_field_data
				SET    duplicates_checksum = ?
				WHERE  mid = ? AND vid = ? AND langcode = ?
			`)
			if err != nil {
				tx.Rollback()
				return fmt.Errorf("prepare update: %w", err)
			}
			for _, p := range batch {
				if _, err := stmt.Exec(p.checksum, p.mid, p.vid, p.langcode); err != nil {
					stmt.Close()
					tx.Rollback()
					return fmt.Errorf("update mid=%d: %w", p.mid, err)
				}
				stats.Updated++
			}
			stmt.Close()
			if err := tx.Commit(); err != nil {
				return fmt.Errorf("commit transaction: %w", err)
			}
		} else if cfg.DryRun {
			stats.Updated += len(batch)
		}

		stats.Processed += batchCount
		log.Printf("  … %d / %d rows processed (%d skipped so far)",
			stats.Processed, total, stats.Skipped)

		if batchCount < cfg.BatchSize {
			break
		}
	}

	action := "updated"
	if cfg.DryRun {
		action = "would update"
	}
	log.Printf("Done — %s %d rows, skipped %d", action, stats.Updated, stats.Skipped)
	log.Println("\nProcessing summary:")
	log.Printf("  Total rows processed : %d", stats.Processed)
	log.Printf("  Rows updated/hashed  : %d", stats.Updated)
	log.Printf("  Rows skipped         : %d", stats.Skipped)
	if stats.FileNotFound > 0 {
		log.Printf("    - Files not found  : %d", stats.FileNotFound)
	}
	if stats.HashErrors > 0 {
		log.Printf("    - Hash errors      : %d", stats.HashErrors)
	}
	if stats.URIErrors > 0 {
		log.Printf("    - URI resolution   : %d", stats.URIErrors)
	}
	return nil
}

// ---- Flag parsing -------------------------------------------------------------

func parseFlags() Config {
	cfg := Config{}
	var bundlesFlag string
	var showVersion bool

	flag.StringVar(&cfg.WebRoot, "web-root", "",
		"Absolute path to the Drupal web root.\n"+
			" Used to resolve relative file paths from Drupal config (e.g. sites/default/files).")
	flag.StringVar(&cfg.PublicDir, "public-dir", "",
		"Override the public files directory. Defaults to the value in Drupal's system.file config.")
	flag.StringVar(&cfg.PrivateDir, "private-dir", "",
		"Override the private files directory. Defaults to the value in Drupal's system.file config.")
	flag.StringVar(&bundlesFlag, "bundles", "image,file,document",
		"Comma-separated list of media bundle names to process.")
	flag.IntVar(&cfg.BatchSize, "batch", 500,
		"Number of rows to process per batch.")
	flag.BoolVar(&cfg.DryRun, "dry-run", false,
		"Resolve and hash files without writing checksums to the database.")
	flag.BoolVar(&cfg.Force, "force", false,
		"Re-hash and overwrite rows that already have a checksum.")
	flag.BoolVar(&cfg.Verbose, "verbose", false,
		"Log each file path and checksum as it is processed.")
	flag.BoolVar(&showVersion, "version", false, "Print the version and exit.")
	flag.BoolVar(&showVersion, "v", false, "Print the version and exit.")

	flag.Parse()

	if showVersion {
		fmt.Println(version)
		os.Exit(0)
	}

	// Parse bundles flag.
	for _, b := range strings.Split(bundlesFlag, ",") {
		if b = strings.TrimSpace(b); b != "" {
			cfg.Bundles = append(cfg.Bundles, b)
		}
	}
	if len(cfg.Bundles) == 0 {
		fmt.Fprintln(os.Stderr, "Error: --bundles must specify at least one bundle name")
		flag.PrintDefaults()
		os.Exit(1)
	}

	// Resolve DSN.
	if os.Getenv("DDEV_DATABASE") != "" {
		cfg.DSN = "db:db@tcp(db:3306)/db"
		log.Println("DDEV environment detected, using DDEV database DSN")
	} else if dsn, err := autoPopulateDSN(); err == nil {
		cfg.DSN = dsn
		log.Println("Using DSN from environment")
	} else {
		fmt.Fprintf(os.Stderr, "Error:\n  could not determine database DSN from environment\n\nUsage:\n")
		flag.PrintDefaults()
		os.Exit(1)
	}

	if cfg.DryRun {
		log.Println("[DRY RUN MODE] No changes will be written to the database")
	}
	if cfg.Verbose {
		log.Println("[VERBOSE MODE] File details will be logged")
	}

	return cfg
}

// ---- Entry point --------------------------------------------------------------

func main() {
	cfg := parseFlags()

	db, err := sql.Open("mysql", cfg.DSN)
	if err != nil {
		log.Fatalf("Failed to open database: %v", err)
	}
	defer db.Close()

	db.SetMaxOpenConns(5)
	db.SetMaxIdleConns(2)
	db.SetConnMaxLifetime(5 * time.Minute)

	if err := db.Ping(); err != nil {
		log.Fatalf("Failed to connect to database: %v", err)
	}
	log.Println("✓ Database connection successful")

	// Resolve file directories: CLI flags take precedence, then Drupal config.
	if cfg.PublicDir == "" || cfg.PrivateDir == "" {
		pubCfg, privCfg, err := readDrupalFilePaths(db)
		if err != nil {
			log.Printf("Warning: could not read Drupal file paths from config table: %v", err)
		} else {
			if cfg.PublicDir == "" && pubCfg != "" {
				resolved, err := resolveDrupalPath(cfg.WebRoot, pubCfg)
				if err != nil {
					log.Fatalf("Cannot resolve public file path %q: %v", pubCfg, err)
				}
				cfg.PublicDir = resolved
				log.Printf("Public dir (from Drupal config): %s", cfg.PublicDir)
			}
			if cfg.PrivateDir == "" && privCfg != "" {
				resolved, err := resolveDrupalPath(cfg.WebRoot, privCfg)
				if err != nil {
					log.Fatalf("Cannot resolve private file path %q: %v", privCfg, err)
				}
				cfg.PrivateDir = resolved
				log.Printf("Private dir (from Drupal config): %s", cfg.PrivateDir)
			}
		}
	}

	if cfg.PublicDir == "" {
		log.Println("Warning: public file directory could not be determined; public:// URIs will fail")
	}
	if cfg.PrivateDir != "" {
		log.Printf("Private dir: %s", cfg.PrivateDir)
	}

	log.Printf("Bundles     : %s", strings.Join(cfg.Bundles, ", "))

	if err := ensureChecksumColumn(db, cfg.DryRun); err != nil {
		log.Fatalf("Failed to ensure duplicates_checksum column: %v", err)
	}

	if err := processMedia(db, cfg); err != nil {
		log.Fatalf("Failed to process media: %v", err)
	}
}
