#!/bin/bash
# Entrypoint wrapper: when DOLI_INIT_DEMO_REALISTIC=1, generate the realistic
# demo data (install/generate-demo.php) once the auto-install has finished.
#
# docker-run.sh (the official entrypoint) only starts Apache after the install
# completes, so "Apache answers" == "install done". A background watcher waits
# for that, runs the generator once, and drops a lock file in the documents
# volume so a container restart never re-generates.

if [ "${DOLI_INIT_DEMO_REALISTIC:-0}" = "1" ]; then
	(
		until curl -fsS -o /dev/null http://127.0.0.1:80/ 2>/dev/null; do
			sleep 5
		done
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
	) &
fi

exec docker-run.sh "$@"
