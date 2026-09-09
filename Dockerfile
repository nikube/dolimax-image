# dolimax — official dolibarr/dolibarr image, any version, made configurable:
#   DOLI_INIT_DEMO_REALISTIC=1          realistic demo company on first boot
#   DOLI_EXTRA_MODULES=<spec>,...       modules installed through DMM on every boot
#                                       (hub name, owner/repo, git URL, spec@ref)
#   DOLI_ACTIVATE_MODULES=modX,modY     core/custom modules activated on every boot
#   DOLI_GITHUB_TOKEN=...               token handed to DMM for private repos
#   DOLI_CRON_KEY=...                   stored as CRON_KEY once modCron exists
# DMM (DoliModuleManager) is baked in and seeded into custom/ on first boot.
# Only CLI scripts are overlaid (no installer UI patch), so the same Dockerfile
# builds against every official tag: docker build --build-arg DOLI_VERSION=22.0.5 .
ARG DOLI_VERSION=23.0.4
FROM dolibarr/dolibarr:${DOLI_VERSION}

# Git ref of nikube/DMM to bake (tag or branch). dev until dmm-install.php ships in a release.
ARG DMM_REF=dev

USER root
RUN set -eux; mkdir -p /opt/extra-custom /tmp/dmm; \
    curl -fsSL "https://codeload.github.com/nikube/DMM/tar.gz/${DMM_REF}" | tar -xz -C /tmp/dmm; \
    mv /tmp/dmm/DMM-*/dolimodulemanager /opt/extra-custom/dolimodulemanager; \
    chown -R www-data:www-data /opt/extra-custom; rm -rf /tmp/dmm

COPY --chown=www-data:www-data generate-demo.php activate-modules.php set-cron-key.php /var/www/html/install/
COPY docker-dolimax.sh /usr/local/bin/docker-dolimax.sh
RUN chmod +x /usr/local/bin/docker-dolimax.sh

# Generic demo dump of the official image is off: the realistic generator replaces it.
ENV DOLI_INIT_DEMO=0

ENTRYPOINT ["docker-dolimax.sh"]
# Overriding ENTRYPOINT resets CMD — restore the base image's.
CMD ["apache2-foreground"]
