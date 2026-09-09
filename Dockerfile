# dolimax — official dolibarr/dolibarr image, any version, made configurable:
#   DOLI_INIT_DEMO_REALISTIC=1          realistic demo company on first boot
#   DOLI_EXTRA_MODULES=<zip url>,...    custom modules fetched into custom/ on first boot
#   DOLI_ACTIVATE_MODULES=modX,modY     modules activated on every boot (idempotent)
#   DOLI_CRON_KEY=...                   stored as CRON_KEY once modCron exists
# Only CLI scripts are overlaid (no installer UI patch), so the same
# Dockerfile builds against every official tag: docker build --build-arg DOLI_VERSION=22.0.5 .
ARG DOLI_VERSION=23.0.4
FROM dolibarr/dolibarr:${DOLI_VERSION}

USER root
RUN apt-get update && apt-get install -y --no-install-recommends unzip && rm -rf /var/lib/apt/lists/*

COPY --chown=www-data:www-data generate-demo.php activate-modules.php set-cron-key.php /var/www/html/install/
COPY docker-dolimax.sh /usr/local/bin/docker-dolimax.sh
RUN chmod +x /usr/local/bin/docker-dolimax.sh

# Generic demo dump of the official image is off: the realistic generator replaces it.
ENV DOLI_INIT_DEMO=0

ENTRYPOINT ["docker-dolimax.sh"]
# Overriding ENTRYPOINT resets CMD — restore the base image's.
CMD ["apache2-foreground"]
