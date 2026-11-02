#!/usr/bin/env bash
#
# End-to-end check in a throwaway WordPress (SQLite): upload detection, the
# fields, the bulk action and the rendered output of every supported block.
# Complements the unit tests, which cannot run WP_HTML_Tag_Processor.
#
# Usage: composer integration           (or bin/integration-check.sh)
#        WP_VERSION=7.1 composer integration
#
# Requires: wp-cli, php with pdo_sqlite and gd, curl, unzip, network access.

set -euo pipefail

PLUGIN_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
WP_VERSION="${WP_VERSION:-latest}"
WPDIR="$(mktemp -d /tmp/aiid-wp.XXXXXX)"
cleanup() { rm -rf "$WPDIR" "/tmp/aiid-sqlite.$$.zip"; }
trap cleanup EXIT

echo "🌐 Throwaway WordPress ${WP_VERSION} (SQLite) ..."
wp core download --path="$WPDIR" --version="$WP_VERSION" --force >/dev/null 2>&1
curl -sL https://downloads.wordpress.org/plugin/sqlite-database-integration.zip -o "/tmp/aiid-sqlite.$$.zip"
unzip -q -o "/tmp/aiid-sqlite.$$.zip" -d "$WPDIR/wp-content/plugins/"
cp "$WPDIR/wp-content/plugins/sqlite-database-integration/db.copy" "$WPDIR/wp-content/db.php"
php -r '$f=$argv[1];$d=$argv[2];$c=file_get_contents($f);
  $c=str_replace("{SQLITE_IMPLEMENTATION_FOLDER_PATH}",$d."/wp-content/plugins/sqlite-database-integration",$c);
  $c=str_replace("{SQLITE_PLUGIN}","sqlite-database-integration/load.php",$c);
  file_put_contents($f,$c);' "$WPDIR/wp-content/db.php" "$WPDIR"
wp config create --path="$WPDIR" --dbname=wp --dbuser=root --dbpass='' --skip-check --force >/dev/null
wp core install --path="$WPDIR" --url=http://localhost --title=AIID \
  --admin_user=admin --admin_password=admin --admin_email=a@b.test --skip-email >/dev/null

ln -s "$PLUGIN_DIR" "$WPDIR/wp-content/plugins/ai-image-disclosure"
wp plugin activate ai-image-disclosure --path="$WPDIR" >/dev/null

echo "🔎 Running checks ..."
wp eval-file "$PLUGIN_DIR/bin/integration-check.php" --path="$WPDIR" --user=admin 2>&1 | grep -v -E '^(Deprecated|PHP Deprecated)'
