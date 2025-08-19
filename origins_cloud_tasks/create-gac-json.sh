#!/usr/bin/env bash

# Generates GAC file from JSON data in the environment variable GOOGLE_APPLICATION_CREDENTIALS_JSON
# See: https://cloud.google.com/docs/authentication/application-default-credentials#GAC

JSON_FILE="$PLATFORM_APP_DIR/google_application_credentials.json"

if [ -n "${GOOGLE_APPLICATION_CREDENTIALS_JSON}" ]; then
  tmp=$(mktemp)
  echo "$GOOGLE_APPLICATION_CREDENTIALS_JSON" | jq '.' > "$tmp"
  jq '.private_key |= gsub("\\n"; "\n")' "$tmp" > "$JSON_FILE"
  rm -f "$tmp"

  export GOOGLE_APPLICATION_CREDENTIALS="$JSON_FILE"
  echo "GAC JSON file created."
else
    echo "Environment variable 'GOOGLE_APPLICATION_CREDENTIALS_JSON' is not set or empty."
    exit 1
fi
