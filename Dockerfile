# syntax=docker/dockerfile:1
# dolimax — Dolibarr from git (any tag or branch, any repo) on the runtime of the
# official dolibarr/dolibarr image, made configurable:
#   DOLI_INIT_DEMO_REALISTIC=1          realistic demo company on first boot
#   DOLI_EXTRA_MODULES=<spec>,...       modules installed through DMM on every boot
#                                       (hub name, owner/repo, git URL, spec@ref)
#   DOLI_ACTIVATE_MODULES=modX,modY     core/custom modules activated on every boot
#   DOLI_GITHUB_TOKEN=...               token handed to DMM for private repos
#   DOLI_CRON_KEY=...                   stored as CRON_KEY once modCron exists
#   DOLI_PHP_INI="k = v\nk = v"          extra php.ini lines (the official PHP_INI_* cover
#                                       memory/upload/post/timezone/allow_url_fopen only)
# DMM (DoliModuleManager) is baked in and seeded into custom/ on first boot.
#   docker build --build-arg DOLI_REF=22.0.5 .
#   docker build --build-arg DOLI_REPO=nikube/dolibarr --build-arg DOLI_REF=nikubepack .

# Runtime only (PHP, extensions, docker-run.sh): the official entrypoint is the same
# file on every published tag and 20+ all run PHP 8.2, so one base serves every ref.
# ponytail: single base; make it a per-ref build arg in CI if a major needs another PHP.
ARG BASE_IMAGE=dolibarr/dolibarr:24.0.0
FROM ${BASE_IMAGE}

# Dolibarr sources: a tag, a branch or a commit sha of DOLI_REPO.
ARG DOLI_REPO=Dolibarr/dolibarr
ARG DOLI_REF=24.0.1
# Git ref of nikube/DMM to bake (tag or branch). dev until dmm-install.php ships in a release.
ARG DMM_REF=dev

USER root
# ftp: Dolibarr FTP module + EDI/backup flows; bcmath: several modules assume it.
RUN docker-php-ext-install ftp bcmath

# Private ERP: nothing to index. Refuse the usual AI/SEO crawlers and tell the rest not to index.
COPY block-bots.conf /etc/apache2/conf-enabled/block-bots.conf
RUN a2enmod headers

COPY generate-demo.php activate-modules.php set-cron-key.php /opt/dolimax-install/

# Replace the base image's Dolibarr with the requested ref (custom/ stays: it is a volume).
# The version is read from the sources and handed to docker-run.sh by the wrapper,
# which compares it with the database version to decide on a migration.
RUN <<'EOF'
set -eux
mkdir /tmp/src /tmp/dmm
curl -fsSL "https://codeload.github.com/${DOLI_REPO}/tar.gz/${DOLI_REF}" | tar -xz -C /tmp/src --strip-components=1
chmod -R u+w /var/www/html
find /var/www/html -mindepth 1 -maxdepth 1 ! -name custom -exec rm -rf {} +
cp -r /tmp/src/htdocs/. /var/www/html/
cp -r /tmp/src/scripts /var/www/
# A fork branch may ship its own generate-demo.php: keep it (no clobber).
cp -n /opt/dolimax-install/*.php /var/www/html/install/

if [ -f /var/www/html/version.inc.php ]; then
	php -r 'require "/var/www/html/version.inc.php"; echo DOL_VERSION;' > /etc/dolimax-doli-version
else
	sed -n "s/.*define('DOL_VERSION', *'\([^']*\)').*/\1/p" /var/www/html/filefunc.inc.php | head -1 > /etc/dolimax-doli-version
fi
grep -q '^[0-9]' /etc/dolimax-doli-version

curl -fsSL "https://codeload.github.com/nikube/DMM/tar.gz/${DMM_REF}" | tar -xz -C /tmp/dmm --strip-components=1
mkdir -p /opt/extra-custom
mv /tmp/dmm/dolimodulemanager /opt/extra-custom/dolimodulemanager

chown -R www-data:www-data /var/www/html /var/www/scripts /opt/extra-custom
chmod -R u-w /var/www/html
rm -rf /tmp/src /tmp/dmm /opt/dolimax-install
EOF

COPY docker-dolimax.sh /usr/local/bin/docker-dolimax.sh
RUN chmod +x /usr/local/bin/docker-dolimax.sh

# Generic demo dump of the official image is off: the realistic generator replaces it.
ENV DOLI_INIT_DEMO=0

ENTRYPOINT ["docker-dolimax.sh"]
# Overriding ENTRYPOINT resets CMD — restore the base image's.
CMD ["apache2-foreground"]
