package main

/*
  Go script to generate a hash based on file content.

  This file will be compiled and used as a binary in the vendor/bin directory of
  the project and as such will only allow filepaths that originate 2 levels up
  from the executable (i.e. the site root) to be hashed.

  To compile for Linux run: GOOS=linux GOARCH=amd64 go build -o dof-dss-filehash .
*/

import (
	"crypto/sha256"
	"fmt"
	"io"
	"os"
	"path/filepath"
	"strings"
)

func isSubpath(base, target string) bool {
	rel, err := filepath.Rel(base, target)
	if err != nil {
		return false
	}
	return rel != ".." && !strings.HasPrefix(rel, ".."+string(filepath.Separator))
}

func main() {
	if len(os.Args) != 2 {
		fmt.Fprintln(os.Stderr, "Usage: filehash <filepath>")
		os.Exit(1)
	}

	filePath := os.Args[1]

	absFile, err := filepath.Abs(filePath)
	if err != nil {
		fmt.Fprintf(os.Stderr, "Error: Unable to resolve file path: %v\n", err)
		os.Exit(2)
	}

	absFile, err = filepath.EvalSymlinks(absFile)
	if err != nil {
		fmt.Fprintf(os.Stderr, "Error: Unable to resolve file path symlinks: %v\n", err)
		os.Exit(2)
	}

	// Determine the document root from environment variables
	var baseDir string
	if ddevRoot := os.Getenv("DDEV_COMPOSER_ROOT"); ddevRoot != "" {
		baseDir = filepath.Join(ddevRoot, "web")
	} else if platformRoot := os.Getenv("PLATFORM_DOCUMENT_ROOT"); platformRoot != "" {
		baseDir = platformRoot
	} else {
		fmt.Fprintln(os.Stderr, "Error: DDEV_COMPOSER_ROOT or PLATFORM_DOCUMENT_ROOT environment variable not set")
		os.Exit(2)
	}

	baseDir, err = filepath.EvalSymlinks(baseDir)
	if err != nil {
		fmt.Fprintf(os.Stderr, "Error: Unable to resolve base directory: %v\n", err)
		os.Exit(2)
	}

	if !isSubpath(baseDir, absFile) {
		fmt.Fprintln(os.Stderr, "Error: file path is outside document root")
		os.Exit(2)
	}

	file, err := os.Open(absFile)
	if err != nil {
		fmt.Fprintln(os.Stderr, "Error: unable to open file:", err)
		os.Exit(2)
	}
	defer file.Close()

	hasher := sha256.New()
	if _, err := io.Copy(hasher, file); err != nil {
		fmt.Fprintln(os.Stderr, "Error: unable to read file:", err)
		os.Exit(2)
	}

	fmt.Printf("%x\n", hasher.Sum(nil))
}
