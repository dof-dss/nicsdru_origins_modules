#!/bin/bash

# Script to find main.go files in subdirectories, compile them for Linux, and move executables
# to the origins_modules/bin directory.
# If creating a new executable you will need to include it in the compiler.json file 

# Function to display usage
usage() {
    echo "Usage: $0"
    echo "  This script searches for main.go files in subdirectories of the current directory"
    echo "  and compiles them for Linux with 'dof-dss-<subdirectory_name>' prefix"
    exit 1
}

# Check if script is run with arguments
if [ $# -gt 0 ]; then
    usage
fi

# Get current directory (where script is located)
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
# Get the parent directory of the script (should be project directory)
PROJECT_DIR="$(dirname "$SCRIPT_DIR")"

# Check if current directory exists
if [ ! -d "$PROJECT_DIR" ]; then
    echo "Error: Project directory '$PROJECT_DIR' does not exist"
    exit 1
fi

echo "Searching for main.go files in subdirectories of '$PROJECT_DIR'..."

# Find all main.go files recursively starting from project directory
find "$PROJECT_DIR" -name "main.go" -type f | while read -r main_file; do
    # Get the directory containing main.go
    dir=$(dirname "$main_file")

    # Get the subdirectory name (parent of the directory containing main.go)
    subdir_name=$(basename "$dir")

    # Create output filename with prefix using subdirectory name
    output_name="dof-dss-${subdir_name}"

    echo "Compiling $subdir_name/main.go..."

    # Create a temporary directory for building
    temp_dir=$(mktemp -d)

    # Copy the entire subdirectory to temp directory
    echo "  Copying $subdir_name/ to temporary directory..."
    cp -r "$dir"/* "$temp_dir/"

    # Change to temp directory and compile
    cd "$temp_dir" || exit 1

    # Compile for Linux
    if GOOS=linux GOARCH=amd64 go build -o "../$output_name" .; then
        echo "  ✓ Compiled successfully to $output_name"

        # Move executable to ../../bin from project directory
        bin_dir="$PROJECT_DIR/../bin"

        if [ -d "$bin_dir" ]; then
            echo "  Moving to $bin_dir/"
            mv "../$output_name" "$bin_dir/"
        else
            echo "  Warning: Bin directory $bin_dir does not exist"
        fi
    else
        echo "  ✗ Failed to compile $subdir_name/main.go"
    fi

    # Clean up temp directory
    cd - > /dev/null
    rm -rf "$temp_dir"

done

echo "Done."
