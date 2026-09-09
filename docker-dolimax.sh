#!/bin/bash
# dolimax entrypoint wrapper around the official docker-run.sh.
# Runs as root before Apache: seeds custom modules. Then a background watcher
# waits for the install to finish (Apache answering == install done) and runs
# the one-shot demo generator, module activation and cron key storage.
CUSTOM=/var/www/html/custom
DOCS=/var/www/documents
mkdir -p "$CUSTOM"

# Modules baked in the image (nikubepack) — never overwrite an existing dir,
# so a copy updated through DMM survives restarts.
seed_dir() {
	for m in "$1"/*/; do
		[ -d "$m" ] || continue
		name=$(basename "$m")
		if [ ! -d "$CUSTOM/$name" ]; then
			echo "[dolimax] seeding custom module $name"
			cp -r "$m" "$CUSTOM/$name"
		fi
	done
}
[ -d /opt/extra-custom ] && seed_dir /opt/extra-custom

# Modules fetched from release zips, once per URL (marker in the custom volume).
FETCHED="$CUSTOM/.dolimax-fetched"
touch "$FETCHED"
for url in ${DOLI_EXTRA_MODULES//,/ }; do
	grep -qxF "$url" "$FETCHED" && continue
	tmp=$(mktemp -d)
	if curl -fsSL "$url" -o "$tmp/m.zip" && unzip -q "$tmp/m.zip" -d "$tmp/x"; then
		seed_dir "$tmp/x"
		echo "$url" >> "$FETCHED"
	else
		echo "[dolimax] ERROR: could not fetch $url"
	fi
	rm -rf "$tmp"
done
chown -R www-data:www-data "$CUSTOM"

if [ "${DOLI_INIT_DEMO_REALISTIC:-0}" = "1" ] || [ -n "$DOLI_ACTIVATE_MODULES" ] || [ -n "$DOLI_CRON_KEY" ]; then
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

		if [ -n "$DOLI_ACTIVATE_MODULES" ]; then
			MLOG=$DOCS/activate-modules.log
			if as_www "php /var/www/html/install/activate-modules.php '$DOLI_ACTIVATE_MODULES'" >> "$MLOG" 2>&1; then
				echo "[dolimax] modules activated: $DOLI_ACTIVATE_MODULES"
			else
				echo "[dolimax] ERROR: module activation failed — see $MLOG"
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
