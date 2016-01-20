<?php

namespace MBeckett\ElggCopy;

const PLUGIN_ID = 'elgg_copy';

elgg_register_event_handler('init', 'system', __NAMESPACE__ . '\\init');

function init() {
	elgg_extend_view("js/admin", "js/elgg_copy/admin");
	
	elgg_register_page_handler('elgg_copy', __NAMESPACE__ . '\\pagehandler');
    
    elgg_register_action('elgg_copy/reset', __DIR__ . "/actions/reset.php", 'admin');
	elgg_register_action('elgg_copy/regenerate_key', __DIR__ . '/actions/regenerate_key.php', 'admin');
	
	elgg_register_plugin_hook_handler('public_pages', 'walled_garden', __NAMESPACE__ . '\\public_pages');

    elgg_register_plugin_hook_handler('register', 'menu:admin_control_panel', __NAMESPACE__ . '\\control_panel');
	
	if (elgg_in_context('admin')) {
		elgg_load_js('lightbox');
		elgg_load_css('lightbox');
	}
}


function export($type = 'all') {
	$dataroot = elgg_get_config('dataroot');
	if (!is_dir($dataroot . 'elgg_copy/export')) {
		mkdir($dataroot . 'elgg_copy/export', 0755, true);
	}
	
	switch ($type) {
		case 'database':
			export_database();
			break;
		case 'dataroot':
			export_dataroot();
			break;
		case 'mod':
			export_mod();
			break;
		case 'settings':
			export_settings();
			break;
		default:
			export_database();
			export_dataroot();
			export_mod();
			export_settings();
			break;
	}
}

function export_database() {
	$dataroot = elgg_get_config('dataroot');
	$dbuser = elgg_get_config('dbuser');
	$dbpass = elgg_get_config('dbpass');
	$dbhost = elgg_get_config('dbhost');
	$dbname = elgg_get_config('dbname');
	$dbprefix = elgg_get_config('dbprefix');

	$mysqldump_path = elgg_get_plugin_setting('mysqldump_path', PLUGIN_ID);
	
	$dump = "{$mysqldump_path}mysqldump -u{$dbuser} -p'{$dbpass}' -h {$dbhost} {$dbname} --ignore-table={$dbname}.{$dbprefix}users_sessions | gzip > {$dataroot}elgg_copy/export/database.sql.gz";
	exec($dump);
}

function export_dataroot() {
	$dataroot = elgg_get_config('dataroot');
	
	// zip up the dataroot, exclude elgg_copy, and stick it in elgg_copy
    exec("cd {$dataroot}; tar -zcvf elgg_copy/export/dataroot.tar.gz . --exclude=./elgg_copy --exclude=./system_cache --exclude=./views_simplecache");
}

function export_mod() {
	$dataroot = elgg_get_config('dataroot');
	$path = elgg_get_config('path');
	
	// zip up the mod directory, exclude elgg_copy, and stick it in elgg_copy
	exec("cd {$path}mod/; zip -r {$dataroot}elgg_copy/export/mod.zip ./* -x \"./elgg_copy/*\"");
}

function export_settings() {
	$dataroot = elgg_get_config('dataroot');
	$path = elgg_get_config('path');
	$dbprefix = elgg_get_config('dbprefix');
	$settings = json_encode(array(
		'dbprefix' => $dbprefix,
		'url' => elgg_get_site_url(),
		'path' => $path,
		'dataroot' => $dataroot,
		'exported' => time()
	));
	file_put_contents($dataroot . 'elgg_copy/export/settings.json', $settings);
}


function generate_key() {
	$secret = get_site_secret();
	return sha1(microtime(true) . $secret);
}


function pagehandler($page) {
	set_time_limit(0);
    
    $request_key = elgg_get_plugin_setting('request_key', PLUGIN_ID);
    if ($page[1] !== $request_key) {
        header('HTTP/1.0 404 Not Found');
        exit;
    }

	$options = array(
		'database',
		'dataroot',
		'mod',
		'settings'
	);
	
	if (in_array($page[0], $options)) {
		echo elgg_view('resources/elgg_copy/download', array(
			'type' => $page[0]
		));
		exit;
	}
	
	return false;
}


function control_panel($hook, $type, $value) {

    $options = array(
        'name' => 'elgg_copy',
        'text' => 'Elgg Copy',
        'href' => 'action/elgg_copy/reset',
        'is_action' => true,
        'link_class' => 'elgg-button elgg-button-action elgg-copy-trigger',
    );

    $value[] = \ElggMenuItem::factory($options);

    return $value;
}

/**
 * Extend the public pages range
 *
 */
function public_pages($hook, $handler, $return, $params){
	$pages = array('elgg_copy/.*');
	return array_merge($pages, $return);
}