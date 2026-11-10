#!/bin/bash
# dolimax entrypoint wrapper around the official docker-run.sh.
# Runs as root before Apache: seeds baked-in modules into custom/. Then a
# background watcher waits for the install to finish (Apache answering ==
# install done) and runs the one-shot demo generator, module activation,
# DMM-driven module installs and cron key storage.
CUSTOM=/var/www/html/custom
DOCS=/var/www/documents
# docker-run.sh compares DOLI_VERSION with the database version; the base image's
# value is not ours: the sources come from git (version recorded at build time).
export DOLI_VERSION="$(cat /etc/dolimax-doli-version)"
mkdir -p "$CUSTOM"
# The official image ships custom/ as 555: DMM (and any installer) needs it
# writable. Not recursive: a bind-mounted module dir belongs to the host user.
chmod u+w "$CUSTOM"
chown www-data:www-data "$CUSTOM"

# Extra php.ini lines, on top of the PHP_INI_* the official entrypoint handles.
if [ -n "$DOLI_PHP_INI" ]; then
	printf '%b\n' "$DOLI_PHP_INI" > "$PHP_INI_DIR/conf.d/99-dolimax.ini"
fi

# Baked-in modules (DMM, and whatever an image on top adds to /opt/extra-custom).
# Never overwrite an existing dir, so a copy updated through DMM survives restarts.
for m in /opt/extra-custom/*/; do
	[ -d "$m" ] || continue
	name=$(basename "$m")
	if [ ! -d "$CUSTOM/$name" ]; then
		echo "[dolimax] seeding custom module $name"
		cp -r "$m" "$CUSTOM/$name"
		chown -R www-data:www-data "$CUSTOM/$name"
	fi
done

# DMM is the installer for DOLI_EXTRA_MODULES: it has to be active first.
ACTIVATE="$DOLI_ACTIVATE_MODULES"
if [ -n "$DOLI_EXTRA_MODULES" ] && [[ ",$ACTIVATE," != *,modDoliModuleManager,* ]]; then
	ACTIVATE="modDoliModuleManager${ACTIVATE:+,$ACTIVATE}"
fi
# The PHP scripts read their input from the environment (su keeps it): no value
# is pasted into a shell string, so a quote in a key or a login cannot break it.
export DOLI_ACTIVATE_MODULES="$ACTIVATE"

if [ "${DOLI_INIT_DEMO_REALISTIC:-0}" = "1" ] || [ -n "$ACTIVATE" ] || [ -n "$DOLI_CRON_KEY" ]; then
	(
		until curl -fsS -o /dev/null http://127.0.0.1:80/ 2>/dev/null; do sleep 5; done
		as_www() { su www-data -s /bin/sh -c "$1"; }

		# install.lock + older database: docker-run.sh does not migrate, Dolibarr sends
		# everyone to /install/. Do not activate or install modules on the old schema.
		# ponytail: major.minor compared as a float, fine while minors stay 0 (true since 5.0).
		if ! as_www 'php -r "require \"/var/www/html/master.inc.php\"; \$v = getDolGlobalString(\"MAIN_VERSION_LAST_UPGRADE\") ?: getDolGlobalString(\"MAIN_VERSION_LAST_INSTALL\"); exit((int) ((float) \$v < (float) DOL_VERSION));"'; then
			echo "[dolimax] database older than the files ($DOLI_VERSION) or not installed: post-install steps skipped, upgrade through /install/ then restart"
			exit 0
		fi

		if [ "${DOLI_INIT_DEMO_REALISTIC:-0}" = "1" ]; then
			LOCK=$DOCS/install.demo-realistic.done
			LOG=$DOCS/generate-demo.log
			if [ -e "$LOCK" ]; then
				echo "[dolimax] demo lock present, skipping generation"
			elif as_www "php /var/www/html/install/generate-demo.php" >> "$LOG" 2>&1; then
				touch "$LOCK"; chown www-data:www-data "$LOCK" "$LOG"
				echo "[dolimax] realistic demo data generated successfully"
			else
				echo "[dolimax] ERROR: demo generation failed — see $LOG"
			fi
		fi

		if [ -n "$ACTIVATE" ]; then
			MLOG=$DOCS/activate-modules.log
			if as_www "php /var/www/html/install/activate-modules.php" >> "$MLOG" 2>&1; then
				echo "[dolimax] modules activated: $ACTIVATE"
			else
				echo "[dolimax] ERROR: module activation failed — see $MLOG"
			fi
		fi

		# Install + activate through DMM (registry row, release resolution, backup).
		# Idempotent: a module already at the wanted version is only re-activated.
		if [ -n "$DOLI_EXTRA_MODULES" ]; then
			ILOG=$DOCS/dmm-install.log
			if as_www "php $CUSTOM/dolimodulemanager/scripts/dmm-install.php --activate ${DOLI_GITHUB_TOKEN:+--token=$DOLI_GITHUB_TOKEN} ${DOLI_EXTRA_MODULES//,/ }" >> "$ILOG" 2>&1; then
				echo "[dolimax] extra modules installed: $DOLI_EXTRA_MODULES"
			else
				echo "[dolimax] ERROR: some extra modules failed — see $ILOG"
			fi
		fi

		# docker-run.sh writes CRON_KEY before modCron exists on a fresh install,
		# so the row is missing; store it ourselves (idempotent).
		if [ -n "$DOLI_CRON_KEY" ]; then
			as_www "php /var/www/html/install/set-cron-key.php" \
				&& echo "[dolimax] CRON_KEY stored" || echo "[dolimax] ERROR: could not store CRON_KEY"
		fi
	) &
fi

exec docker-run.sh "$@"
