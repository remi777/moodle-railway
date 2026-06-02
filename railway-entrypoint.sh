#!/usr/bin/env bash
set -euo pipefail

# Ensure only one MPM module is enabled (prefork)
a2dismod mpm_event mpm_worker >/dev/null 2>&1 || true
a2enmod mpm_prefork >/dev/null 2>&1 || true

# Remove any leftover symlinks that could cause conflicts
rm -f /etc/apache2/mods-enabled/mpm_event.* /etc/apache2/mods-enabled/mpm_worker.* || true
ln -sf /etc/apache2/mods-available/mpm_prefork.load /etc/apache2/mods-enabled/mpm_prefork.load || true
ln -sf /etc/apache2/mods-available/mpm_prefork.conf /etc/apache2/mods-enabled/mpm_prefork.conf || true

# Fix permissions for the Railway Volume (moodledata)
mkdir -p /var/www/moodledata
chown -R www-data:www-data /var/www/moodledata
chmod -R 0775 /var/www/moodledata

# Log which MPM module is loaded
apache2ctl -M 2>/dev/null | grep mpm || true

# Railway reverse-proxy: treat request as HTTPS when the proxy indicates it
echo "SetEnvIf X-Forwarded-Proto https HTTPS=on" > /etc/apache2/conf-available/railway-proxy.conf
a2enconf railway-proxy >/dev/null 2>&1 || true

# Moodle 5.x serves from the public/ subdirectory. The base image configures
# Apache from $APACHE_DOCUMENT_ROOT, so point it at the public webroot.
export APACHE_DOCUMENT_ROOT="${APACHE_DOCUMENT_ROOT:-/var/www/html/public}"

# Generate config.php from environment variables when DB settings are provided.
# The file only references getenv(), so no secrets are baked into the image and
# the connection survives every redeploy (no install wizard reset).
PUBLIC_DIR=/var/www/html/public
if [ -n "${MOODLE_DB_HOST:-}" ]; then
  echo "# Writing ${PUBLIC_DIR}/config.php from environment"
  cat > "${PUBLIC_DIR}/config.php" <<'PHP'
<?php  // Moodle config generated for Railway. Values come from environment variables.
unset($CFG);
global $CFG;
$CFG = new stdClass();

$CFG->dbtype    = getenv('MOODLE_DB_TYPE') ?: 'mariadb';
$CFG->dblibrary = 'native';
$CFG->dbhost    = getenv('MOODLE_DB_HOST');
$CFG->dbname    = getenv('MOODLE_DB_NAME');
$CFG->dbuser    = getenv('MOODLE_DB_USER');
$CFG->dbpass    = getenv('MOODLE_DB_PASS');
$CFG->prefix    = getenv('MOODLE_DB_PREFIX') ?: 'mdl_';
$CFG->dboptions = [
    'dbpersist'   => 0,
    'dbport'      => getenv('MOODLE_DB_PORT') ?: 3306,
    'dbsocket'    => '',
    'dbcollation' => 'utf8mb4_unicode_ci',
];

$CFG->wwwroot   = rtrim(getenv('MOODLE_WWWROOT') ?: ('https://' . getenv('RAILWAY_PUBLIC_DOMAIN')), '/');
$CFG->dataroot  = getenv('MOODLE_DATAROOT') ?: '/var/www/moodledata';
$CFG->admin     = 'admin';
$CFG->directorypermissions = 0777;
$CFG->sslproxy  = true;  // Railway terminates TLS in front of the container.

require_once(__DIR__ . '/lib/setup.php');
PHP
  chown www-data:www-data "${PUBLIC_DIR}/config.php"

  # Apply any pending database upgrade non-interactively (idempotent: a no-op
  # when the schema already matches the code).
  echo "# Running Moodle CLI upgrade (non-interactive)"
  su -s /bin/sh -c "php '${PUBLIC_DIR}/admin/cli/upgrade.php' --non-interactive --allow-unstable" www-data \
    || echo "WARN: CLI upgrade returned non-zero (already up to date, or DB not installed yet)"
fi

# Start the original entrypoint + Apache
exec /usr/local/bin/moodle-docker-php-entrypoint apache2-foreground
