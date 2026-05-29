/*
  Go script to generate a hashes for media bundle files.

  To compile for Linux run: GOOS=linux GOARCH=amd64 go build -o dof-dss-mediahasher .
*/

package main

import (
	"database/sql"
	"flag"
	"fmt"
	"log"
	"os"
	"os/exec"
	"path/filepath"
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
	BatchSize  int
	DryRun     bool
	Force      bool
	Verbose    bool
}

// Media bundle table and target ID column.
type BundleTable struct {
	Table  string
	Column string
}

// List of media bundle tables and the target ID columns.
var bundleTables = []BundleTable{
	{Table: "media__field_media_file", Column: "field_media_file_target_id"},
	{Table: "media__field_media_image", Column: "field_media_image_target_id"},
	{Table: "media__field_media_video_file", Column: "field_media_video_file_target_id"},
	{Table: "media__field_media_file_1", Column: "field_media_file_1_target_id"},
}

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

	if err := ensureChecksumColumn(db, cfg.DryRun); err != nil {
		log.Fatalf("Failed to ensure duplicates_checksum column: %v", err)
	}

	if err := processMedia(db, cfg); err != nil {
		log.Fatalf("Failed to process media: %v", err)
	}
}

// Parse and validate command-line flags, returning a Config struct or exiting with an error.
func parseFlags() Config {
	cfg := Config{}
	var showVersion bool

	flag.StringVar(&cfg.DSN, "dsn", "",
		"Required. MySQL DSN.\n Example: db:db@tcp(db:3306)/db")
	flag.StringVar(&cfg.WebRoot, "web-root", "",
		"Required. Absolute path to the Drupal web root.\n Example: /var/www/html/web")
	flag.StringVar(&cfg.PublicDir, "public-dir", "",
		"Absolute path to the public files directory.\n Defaults to <web-root>/sites/default/files")
	flag.StringVar(&cfg.PrivateDir, "private-dir", "",
		"Absolute path to the private files directory (optional).\n Required only when private:// URIs are present.")
	flag.IntVar(&cfg.BatchSize, "batch", 500,
		"Number of rows to process per batch.")
	flag.BoolVar(&cfg.DryRun, "dry-run", false,
		"Resolve and hash files without writing checksums to the database.")
	flag.BoolVar(&cfg.Force, "force", false,
		"Re-hash and overwrite rows that already have a checksum.")
	flag.BoolVar(&cfg.Verbose, "verbose", false,
		"Log each file path and checksum as it is processed.")
	flag.BoolVar(&showVersion, "version", false,
		"Print the version and exit.")
	flag.BoolVar(&showVersion, "v", false,
		"Print the version and exit.")

	flag.Parse()

	if showVersion {
		fmt.Println(version)
		os.Exit(0)
	}

	var errs []string

	if cfg.DSN == "" {
		errs = append(errs, "  --dsn is required")
	}
	if cfg.WebRoot == "" {
		errs = append(errs, "  --web-root is required")
	} else if abs, err := filepath.Abs(cfg.WebRoot); err != nil {
		errs = append(errs, fmt.Sprintf("  --web-root: %v", err))
	} else {
		cfg.WebRoot = abs
		if _, err := os.Stat(cfg.WebRoot); err != nil {
			errs = append(errs, fmt.Sprintf("  --web-root %q does not exist", cfg.WebRoot))
		}
	}

	if cfg.PublicDir == "" && cfg.WebRoot != "" {
		cfg.PublicDir = filepath.Join(cfg.WebRoot, "sites", "default", "files")
	}
	if cfg.PublicDir != "" {
		if abs, err := filepath.Abs(cfg.PublicDir); err != nil {
			errs = append(errs, fmt.Sprintf("  --public-dir: %v", err))
		} else {
			cfg.PublicDir = abs
			if _, err := os.Stat(cfg.PublicDir); err != nil {
				errs = append(errs, fmt.Sprintf("  --public-dir %q does not exist", cfg.PublicDir))
			}
		}
	}

	if cfg.PrivateDir != "" {
		if abs, err := filepath.Abs(cfg.PrivateDir); err != nil {
			errs = append(errs, fmt.Sprintf("  --private-dir: %v", err))
		} else {
			cfg.PrivateDir = abs
			if _, err := os.Stat(cfg.PrivateDir); err != nil {
				errs = append(errs, fmt.Sprintf("  --private-dir %q does not exist", cfg.PrivateDir))
			}
		}
	}

	if len(errs) > 0 {
		fmt.Fprintf(os.Stderr, "Error:\n%s\n\nUsage:\n", strings.Join(errs, "\n"))
		flag.PrintDefaults()
		os.Exit(1)
	}

	log.Printf("Web root   : %s", cfg.WebRoot)
	log.Printf("Public dir : %s", cfg.PublicDir)
	if cfg.PrivateDir != "" {
		log.Printf("Private dir: %s", cfg.PrivateDir)
	}

	return cfg
}

// Fetch the URI for a given media entity and revision ID.
func resolveFileURI(db *sql.DB, mid, vid int64, langcode string) (string, error) {
	for _, ft := range bundleTables {
		// Construct the SQL query for the current bundle type.
		query := fmt.Sprintf(
			"SELECT `%s` FROM `%s` WHERE entity_id = ? AND revision_id = ? AND langcode = ? AND deleted = 0 LIMIT 1",
			ft.Column, ft.Table,
		)

		// Fetch the target FID for the current Media and revision ID.
		var fid int64
		err := db.QueryRow(query, mid, vid, langcode).Scan(&fid)
		if err == sql.ErrNoRows || fid == 0 {
			continue
		}

		// Any error other than "no rows" or "table doesn't exist" is fatal.
		if err != nil {
			// MySQL error 1146 = table doesn't exist — skip gracefully.
			if strings.Contains(err.Error(), "doesn't exist") || strings.Contains(err.Error(), "1146") {
				continue
			}
			return "", fmt.Errorf("query %s for mid=%d: %w", ft.Table, mid, err)
		}

		// Fetch the URI from file_managed for the target FID.
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

// Converts a URI to an absolute filesystem path based the args or defaults.
func uriToPath(cfg Config, uri string) (string, error) {
	switch {
	case strings.HasPrefix(uri, "public://"):
		rel := strings.TrimPrefix(uri, "public://")
		return filepath.Join(cfg.PublicDir, filepath.FromSlash(rel)), nil

	case strings.HasPrefix(uri, "private://"):
		if cfg.PrivateDir == "" {
			return "", fmt.Errorf("private:// URI requires --private-dir to be set")
		}
		rel := strings.TrimPrefix(uri, "private://")
		return filepath.Join(cfg.PrivateDir, filepath.FromSlash(rel)), nil

	default:
		if filepath.IsAbs(uri) {
			return uri, nil
		}
		return "", fmt.Errorf("Unrecognised URI scheme: %q", uri)
	}
}

// Call the dof-dss-filehash to generate the hash for the given path.
func hashFile(path string) (string, error) {
	cmd := exec.Command("dof-dss-filehash", path)
	output, err := cmd.Output()
	if err != nil {
		if exitErr, ok := err.(*exec.ExitError); ok {
			code := exitErr.ExitCode()
			if code == 1 || code == 2 {
				return "", fmt.Errorf("Filehash failed for %q with exit code %d", path, code)
			}
		}
		return "", fmt.Errorf("Filehash failed running filehash %q: %w", path, err)
	}

	checksum := strings.TrimSpace(string(output))
	if checksum == "" {
		return "", fmt.Errorf("Filehash produced empty output for %q", path)
	}
	return checksum, nil
}

// Check for the existance of the duplicates_checksum column and add it if missing.
func ensureChecksumColumn(db *sql.DB, dryRun bool) error {
	// Use a SELECT on the table instead of looking up information_schema
	// which we might not have acccess to.
	_, err := db.Exec("SELECT duplicates_checksum FROM media_field_data LIMIT 0")
	if err == nil {
		log.Println("duplicates_checksum column already exists")
		return nil
	}
	// Print errors that are not related to the missing column.
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
	log.Println("Checksum column added to media_field_data")
	return nil
}

// Fetch media bundle rows in batches, resolve the file URIs, generate the hash and update the update the database.
func processMedia(db *sql.DB, cfg Config) error {
	for _, ft := range bundleTables {
		log.Printf("Field table: %s (column: %s)", ft.Table, ft.Column)
	}

	total, err := countRows(db, cfg)
	if err != nil {
		return err
	}
	log.Printf("Processing %d rows (batch size: %d) …", total, cfg.BatchSize)

	var processed, updated, skipped, offset int

	for {
		selectSQL := "SELECT mid, vid, langcode FROM media_field_data"
		if !cfg.Force {
			selectSQL += " WHERE duplicates_checksum IS NULL"
		}
		selectSQL += " ORDER BY mid, vid, langcode LIMIT ? OFFSET ?"

		rows, err := db.Query(selectSQL, cfg.BatchSize, offset)
		if err != nil {
			return fmt.Errorf("query batch at offset %d: %w", offset, err)
		}

		type pending struct {
			mid      int64
			vid      int64
			langcode string
			checksum string
		}
		var batch []pending
		var batchCount int

		for rows.Next() {
			var mid, vid int64
			var langcode string
			if err := rows.Scan(&mid, &vid, &langcode); err != nil {
				rows.Close()
				return fmt.Errorf("scan row: %w", err)
			}
			batchCount++

			uri, err := resolveFileURI(db, mid, vid, langcode)
			if err != nil {
				if cfg.Verbose {
					log.Printf("  [skip] mid=%d: %v", mid, err)
				}
				skipped++
				continue
			}

			path, err := uriToPath(cfg, uri)
			if err != nil {
				if cfg.Verbose {
					log.Printf("  [skip] mid=%d uri=%q: %v", mid, uri, err)
				}
				skipped++
				continue
			}

			absPath, err := filepath.Abs(path)
			if err != nil {
				if cfg.Verbose {
					log.Printf("  [skip] mid=%d path=%q: %v", mid, path, err)
				}
				skipped++
				continue
			}

			checksum, err := hashFile(absPath)
			if err != nil {
				if cfg.Verbose {
					log.Printf("  [skip] mid=%d path=%q: %v", mid, path, err)
				}
				skipped++
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
				updated++
			}
			stmt.Close()
			if err := tx.Commit(); err != nil {
				return fmt.Errorf("commit transaction: %w", err)
			}
		} else if cfg.DryRun {
			updated += len(batch)
		}

		processed += batchCount
		offset += cfg.BatchSize
		log.Printf("  … %d / %d rows processed (%d skipped so far)", processed, total, skipped)

		if offset >= total {
			break
		}
	}

	action := "updated"
	if cfg.DryRun {
		action = "would update"
	}
	log.Printf("Done — %s %d rows, skipped %d", action, updated, skipped)
	return nil
}

// Fetch all of the media rows if --force, or just those that are NULL.
func countRows(db *sql.DB, cfg Config) (int, error) {
	query := "SELECT COUNT(*) FROM media_field_data"

	if !cfg.Force {
		query += " WHERE duplicates_checksum IS NULL"
	}

	var row int
	err := db.QueryRow(query).Scan(&row)
	return row, err
}
