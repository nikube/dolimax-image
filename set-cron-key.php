<?php
// CLI only: store DOLI_CRON_KEY as CRON_KEY (the official entrypoint does this
// UPDATE before modCron exists, so the row is missing on a fresh install).
if (php_sapi_name() !== 'cli') { http_response_code(403); exit(1); }
require __DIR__.'/../master.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
$key = $argv[1] ?? getenv('DOLI_CRON_KEY');
if (empty($key)) { print "No key\n"; exit(0); }
$res = dolibarr_set_const($db, 'CRON_KEY', $key, 'chaine', 0, '', 0);
print ($res > 0 ? "CRON_KEY set\n" : "CRON_KEY update failed\n");
exit($res > 0 ? 0 : 1);
