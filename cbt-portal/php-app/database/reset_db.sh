#!/usr/bin/env bash
# macOS / Linux twin of reset_db.bat: drops and rebuilds cbt_portal from the committed SQL files.
# Usage: database/reset_db.sh            (uses `mysql -u root`; set MYSQL="mysql -u root -pSECRET" to override)
set -euo pipefail
MYSQL=${MYSQL:-"mysql -u root"}
DIR="$(cd "$(dirname "$0")" && pwd)"

echo "Dropping and recreating cbt_portal..."
$MYSQL -e "DROP DATABASE IF EXISTS cbt_portal; CREATE DATABASE cbt_portal CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
for f in schema seed rrb_ntpc wbcs_prelims wbjee jenpas gnm_anm presets_extra wbpsc_clerkship presets_extra2; do
  echo "Importing $f.sql"
  $MYSQL cbt_portal < "$DIR/$f.sql"
done
echo "Done. cbt_portal now matches the committed SQL files exactly."
