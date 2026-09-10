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

func main() {
	if len(os.Args) != 2 {
		fmt.Fprintln(os.Stderr, "Usage: filehash <filepath>")
		os.Exit(1)
	}

	filePath := os.Args[1]

	exePath, err := os.Executable()
	if err != nil {
		fmt.Fprintf(os.Stderr, "Error: Unable to resolve executable path: %v\n", err)
		os.Exit(2)
	}

	// Set the baseDir to the executable directory and up 2 levels (because we're in vendor/bin)
	// and resolve to the absolute path including symlinks to ensure we have the correct site root.
	baseDir := filepath.Clean(filepath.Join(filepath.Dir(exePath), "..", ".."))
	baseDir, err = filepath.EvalSymlinks(baseDir)
	if err != nil {
		fmt.Fprintf(os.Stderr, "Error: Unable to resolve base directory: %v\n", err)
		os.Exit(2)
	}

	absFile, err := filepath.Abs(filePath)
	if err != nil {
		fmt.Fprintf(os.Stderr, "Error:Unable to resolve file path: %v\n", err)
		os.Exit(2)
	}

	absFile, err = filepath.EvalSymlinks(absFile)
	if err != nil {
		fmt.Fprintf(os.Stderr, "Error: Unable to resolve file path symlinks: %v\n", err)
		os.Exit(2)
	}

	// Check the file path relative to the baseDir to ensure it's within the site root.
	// If the relative path starts with '..', it's outside the site root.
	rel, err := filepath.Rel(baseDir, absFile)
	if err != nil || strings.HasPrefix(rel, "..") {
		fmt.Fprintln(os.Stderr, "Error: file path is outside site root")
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
