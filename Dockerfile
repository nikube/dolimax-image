# Dolibarr 23.x with nikube's feature/demo-data-installer overlay.
# Adds an optional "Load demo data" step (realistic sample company) to the
# installer. Only the 5 files changed by that branch are overlaid on top of
# the official image — no full rebuild needed.
# Bump the tag below and push to main: the GitHub Actions workflow rebuilds
# and publishes ghcr.io/nikube/dolibarr-demo:<tag> automatically.
FROM dolibarr/dolibarr:23.0.3

# Installer pages + demo generator
COPY overlay/install/generate-demo.php        /var/www/html/install/generate-demo.php
COPY overlay/install/install.forced.sample.php /var/www/html/install/install.forced.sample.php
COPY overlay/install/step4.php                /var/www/html/install/step4.php
COPY overlay/install/step5.php                /var/www/html/install/step5.php
COPY overlay/langs/en_US/install.lang         /var/www/html/langs/en_US/install.lang

# Keep ownership consistent with the base image (www-data uid/gid 33).
USER root
RUN chown www-data:www-data \
      /var/www/html/install/generate-demo.php \
      /var/www/html/install/install.forced.sample.php \
      /var/www/html/install/step4.php \
      /var/www/html/install/step5.php \
      /var/www/html/langs/en_US/install.lang
