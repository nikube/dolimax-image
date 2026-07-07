<?php
/* Copyright (C) 2026       Nicolas Zaou            <nz@anatoleconseil.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 *	\file       htdocs/install/activate-modules.php
 *	\brief      Activate a comma-separated list of Dolibarr modules (CLI only).
 *              Usage: php activate-modules.php modSociete,modDoliModuleManager
 *              Falls back to the DOLI_ACTIVATE_MODULES environment variable.
 *              Works for core and custom modules (activateModule() searches
 *              all module directories, including the custom alt root).
 */

if (php_sapi_name() !== 'cli') {
	http_response_code(403);
	print "This script is CLI only.\n";
	exit(1);
}

if (!defined('DOL_DOCUMENT_ROOT')) {
	require __DIR__.'/../master.inc.php';
}
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';

$list = !empty($argv[1]) ? $argv[1] : (getenv('DOLI_ACTIVATE_MODULES') ?: '');
$mods = array_filter(array_map('trim', explode(',', $list)));
if (empty($mods)) {
	print "No modules given (argv[1] or DOLI_ACTIVATE_MODULES).\n";
	exit(0);
}

print "***** activate-modules.php (".DOL_VERSION.") *****\n";
$nberr = 0;
foreach ($mods as $mod) {
	$res = activateModule($mod, 1);
	if (!empty($res['errors'])) {
		$nberr++;
		print 'ERROR activating '.$mod.': '.implode(', ', $res['errors'])."\n";
	} else {
		print 'Activated '.$mod."\n";
	}
	$conf->setValues($db);
}
exit($nberr ? 1 : 0);
