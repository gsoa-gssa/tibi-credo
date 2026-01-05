#!/bin/bash

set -e

# Configurable variables
REMOTE="certimi"
FOLDER="/home/clients/023ec5148fdd973d087fba617cc77731/sites/certimi.lukas-buergi.ch"
MAX_UNCOMPRESSED=30

# Generate a timestamped filename
TIMESTAMP=$(date +"%Y%m%d_%H%M%S")
SQL_FILE="backup-${TIMESTAMP}.sql"

# Get DB credentials from remote .env
readarray -t DB_CREDS < <(ssh "$REMOTE" "cd '$FOLDER' && grep -E 'DB_(HOST|PORT|DATABASE|USERNAME|PASSWORD)=' .env")
for line in "${DB_CREDS[@]}"; do
    export "$line"
done

# Dump the database on remote
ssh "$REMOTE" "cd '$FOLDER' && mysqldump -h\"$DB_HOST\" -P\"${DB_PORT:-3306}\" -u\"$DB_USERNAME\" -p\"$DB_PASSWORD\" \"$DB_DATABASE\" > \"$SQL_FILE\""

# Copy the dump to local
scp "$REMOTE:$FOLDER/$SQL_FILE" "./$SQL_FILE"

# Optionally remove the remote dump
ssh "$REMOTE" "rm '$FOLDER/$SQL_FILE'"

# Count uncompressed .sql files
UNCOMPRESSED_COUNT=$(ls -1 *.sql 2>/dev/null | wc -l)

# Compress all .sql files if count exceeds MAX_UNCOMPRESSED
if [ "$UNCOMPRESSED_COUNT" -gt "$MAX_UNCOMPRESSED" ]; then
    tar -cvzf "sql_backups_$(date +%Y%m%d_%H%M%S).tar.gz" --use-compress-program="gzip --best" *.sql
    rm *.sql
    echo "Compressed all .sql files into a tar.gz archive."
fi