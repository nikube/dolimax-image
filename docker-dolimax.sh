#!/bin/bash
# dolimax entrypoint wrapper around the official docker-run.sh.
# Runs as root before Apache: seeds baked-in modules into custom/. Then a
# background watcher waits for the install to finish (Apache answering ==
# install done) and runs the one-shot demo generator, module activation,
# DMM-driven module installs and cron key storage.
CUSTOM=/var/www/html/custom
DOCS=/var/www/documents
mkdir -p "$CUSTOM"
# The official image ships custom/ as 555: DMM (and any installer) needs it
# writable. Not recursive: a bind-mounted module dir belongs to the host user.
chmod u+w "$CUSTOM"
chown www-data:www-data "$CUSTOM"

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

if [ "${DOLI_INIT_DEMO_REALISTIC:-0}" = "1" ] || [ -n "$ACTIVATE" ] || [ -n "$DOLI_CRON_KEY" ]; then
	(
		until curl -fsS -o /dev/null http://127.0.0.1:80/ 2>/dev/null; do sleep 5; done
		as_www() { su www-data -s /bin/sh -c "$1"; }

		if [ "${DOLI_INIT_DEMO_REALISTIC:-0}" = "1" ]; then
			LOCK=$DOCS/install.demo-realistic.done
			LOG=$DOCS/generate-demo.log
			if [ -e "$LOCK" ]; then
				echo "[dolimax] demo lock present, skipping generation"
			elif as_www "php /var/www/html/install/generate-demo.php '${DOLI_ADMIN_LOGIN:-admin}'" >> "$LOG" 2>&1; then
				touch "$LOCK"; chown www-data:www-data "$LOCK" "$LOG"
				echo "[dolimax] realistic demo data generated successfully"
			else
				echo "[dolimax] ERROR: demo generation failed — see $LOG"
			fi
		fi

		if [ -n "$ACTIVATE" ]; then
			MLOG=$DOCS/activate-modules.log
			if as_www "php /var/www/html/install/activate-modules.php '$ACTIVATE'" >> "$MLOG" 2>&1; then
				echo "[dolimax] modules activated: $ACTIVATE"
			else
				echo "[dolimax] ERROR: module activation failed — see $MLOG"
			fi
		fi

		# Install + activate through DMM (registry row, release resolution, backup).
		# Idempotent: an already installed module is re-deployed at the same version.
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
			as_www "php /var/www/html/install/set-cron-key.php '$DOLI_CRON_KEY'" \
				&& echo "[dolimax] CRON_KEY stored" || echo "[dolimax] ERROR: could not store CRON_KEY"
		fi
	) &
fi

exec docker-run.sh "$@"
