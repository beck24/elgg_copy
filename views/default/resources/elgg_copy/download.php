<?php

namespace Arck\ElggCopy;

$type = $vars['type'];

$options = array(
	'database' => 'elgg_copy/export/database.sql.gz',
    'dataroot' => 'elgg_copy/export/dataroot.tar.gz',
	'mod' => 'elgg_copy/export/mod.zip',
	'settings' => 'elgg_copy/export/settings.json'
);

$dataroot = elgg_get_config('dataroot');

if (!$options[$type]) {
	return;
}

error_log('[elgg_copy] exporting ' . $type);
export($type);

$fname = basename($options[$type]);
$file = $dataroot . $options[$type];

if (is_file($file)) {
	if (!headers_sent()) {
		header("Pragma: no-cache");
		header("Cache-Control: no-cache, must-revalidate");
		header("Content-type: application/octet-stream");
		header("Content-Disposition: attachment; filename=\"" . $fname . "\"");
		header("Content-Length: " . filesize($file));

		ob_end_clean(); //required here or large files will not work
		ob_end_flush();
		readfile($file);
	} else {
		error_log('[elgg_copy] cannot send file, headers already sent');
	}
} else {
	error_log('[elgg_copy] file ' . $file . ' was not found');
}

exit;
