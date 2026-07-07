#!/bin/bash
# Entrypoint wrapper: when DOLI_INIT_DEMO_REALISTIC=1, generate the realistic
# demo data (install/generate-demo.php) once the auto-install has finished.
#
# docker-run.sh (the official entrypoint) only starts Apache after the install
# completes, so "Apache answers" == "install done". A background watcher waits
# for that, runs the generator once, and drops a lock file in the documents
# volume so a container restart never re-generates.

# Seed pre-bundled custom modules (baked in the image under /opt/extra-custom)
# into the custom dir, which is usually a mounted volume that starts empty.
# Never overwrites an existing module dir, so DMM-updated copies survive.
if [ -d /opt/extra-custom ]; then
	mkdir -p /var/www/html/custom
	for m in /opt/extra-custom/*/; do
		name=$(basename "$m")
		if [ ! -d "/var/www/html/custom/$name" ]; then
			echo "[init-demo] seeding custom module $name"
			cp -r "$m" "/var/www/html/custom/$name"
			chown -R www-data:www-data "/var/www/html/custom/$name"
		fi
	done
fi

if [ "${DOLI_INIT_DEMO_REALISTIC:-0}" = "1" ] || [ -n "${DOLI_ACTIVATE_MODULES}" ]; then
	(
		until curl -fsS -o /dev/null http://127.0.0.1:80/ 2>/dev/null; do
			sleep 5
		done
		if [ "${DOLI_INIT_DEMO_REALISTIC:-0}" = "1" ]; then
			LOCK=/var/www/documents/install.demo-realistic.done
			LOG=/var/www/documents/generate-demo.log
			if [ ! -e "$LOCK" ]; then
				echo "[init-demo] install detected, generating realistic demo data (log: $LOG)"
				if su www-data -s /bin/sh -c "php /var/www/html/install/generate-demo.php '${DOLI_ADMIN_LOGIN:-admin}'" >> "$LOG" 2>&1; then
					touch "$LOCK"
					chown www-data:www-data "$LOCK" "$LOG" 2>/dev/null
					echo "[init-demo] realistic demo data generated successfully"
				else
					echo "[init-demo] ERROR: demo generation failed — see $LOG"
				fi
			else
				echo "[init-demo] lock file present, skipping demo generation"
			fi
		fi
		# Activate extra modules on every boot (idempotent) — core or custom,
		# e.g. DOLI_ACTIVATE_MODULES=modDoliModuleManager
		if [ -n "${DOLI_ACTIVATE_MODULES}" ]; then
			MLOG=/var/www/documents/activate-modules.log
			echo "[init-demo] activating modules: ${DOLI_ACTIVATE_MODULES} (log: $MLOG)"
			if su www-data -s /bin/sh -c "php /var/www/html/install/activate-modules.php '${DOLI_ACTIVATE_MODULES}'" >> "$MLOG" 2>&1; then
				echo "[init-demo] modules activated successfully"
			else
				echo "[init-demo] ERROR: module activation failed — see $MLOG"
			fi
		fi
	) &
fi

exec docker-run.sh "$@"
