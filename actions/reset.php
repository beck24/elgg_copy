<?php

namespace MBeckett\ElggCopy;

set_time_limit(0); // 2 hours

_elgg_services()->db->disableQueryCache();

$dataroot = elgg_get_config('dataroot');
if (!is_dir($dataroot . 'elgg_copy/import')) {
    mkdir($dataroot . 'elgg_copy/import', 0755, true);
}


$path = elgg_get_config('path');
$dbuser = elgg_get_config('dbuser');
$dbpass = elgg_get_config('dbpass');
$dbhost = elgg_get_config('dbhost');
$dbname = elgg_get_config('dbname');
$dbprefix = elgg_get_config('dbprefix');
$plugins_path = elgg_get_plugins_path();
$url = elgg_get_site_url();

$elgg_copy_plugin = elgg_get_plugin_from_id(PLUGIN_ID);
$master_url = elgg_get_plugin_setting('master_url', PLUGIN_ID);
$master_request_key = elgg_get_plugin_setting('master_request_key', PLUGIN_ID);
$key = elgg_get_plugin_setting('request_key', PLUGIN_ID);
$site_name = elgg_get_site_entity()->name;
$site_email = elgg_get_site_entity()->email;
$enable_plugins = elgg_get_plugin_setting('enable_plugins', PLUGIN_ID);
$mysqldump_path = elgg_get_plugin_setting('mysqldump_path', PLUGIN_ID);
$update_dataroot = elgg_get_plugin_setting('update_dataroot', PLUGIN_ID);
$update_mod = elgg_get_plugin_setting('update_mod', PLUGIN_ID);
$update_database = elgg_get_plugin_setting('update_database', PLUGIN_ID);

$settings_json = $dataroot . '/elgg_copy/import/settings.json';
$database_sql = $dataroot . '/elgg_copy/import/database.sql.gz';
$dataroot_zip = $dataroot . '/elgg_copy/import/dataroot.tar.gz';

if (!$master_url || !$master_request_key) {
    register_error('Invalid URL or request key');
    forward(REFERER);
}

logout(); // if the incoming db doesn't have the current user it's messy
elgg_set_ignore_access(true);

@unlink($settings_json);
@unlink($database_sql);
@unlink($dataroot_zip);
error_log('getting dataroot from: ' . $master_url);
if ($update_dataroot) {
    // get our dataroot
    error_log('[elgg_copy] fetching remote data directory - this may take a while');
    $curl = "cd {$dataroot}elgg_copy/import; curl -fsS -m 7200 {$master_url}elgg_copy/dataroot/{$master_request_key} -o dataroot.tar.gz";
    exec($curl);

    if (!is_file($dataroot_zip) || !filesize($dataroot_zip)) {
        error_log("[elgg_copy] Could not retrieve dataroot");
        register_error("[elgg_copy] Could not retrieve dataroot");
        exit;
    }

    error_log('[elgg_copy] unzipping dataroot');
    exec("cd {$dataroot}elgg_copy/import; tar -xzf dataroot.tar.gz");
    exec("rm {$dataroot}elgg_copy/import/dataroot.tar.gz");
    error_log('[elgg_copy] deleting existing dataroot');
    $files = glob($dataroot . '*', GLOB_MARK);
    foreach ($files as $file) {
        if (strpos(basename($file), 'elgg_copy') !== 0) {
            error_log('[elgg_copy] deleting ' . $file);
            exec("rm -rf {$file}");
            if (is_dir($file) || is_file($file)) {
                error_log('[elgg_copy] could not delete ' . $file);
            }
        }
    }

    // move the dataroot back into place
    error_log('[elgg_copy] moving dataroot into place');
    exec("mv {$dataroot}elgg_copy/import/* {$dataroot}");
}

if ($update_mod) {
    // get our mod
    error_log('[elgg_copy] fetching remote mod directory - this may take a while');
    $curl = "cd {$dataroot}elgg_copy/import; curl -fsS -m 7200 {$master_url}elgg_copy/mod/{$master_request_key} -o mod.zip;";
    exec($curl);

    if (!is_file($dataroot . 'elgg_copy/import/mod.zip') || !filesize($dataroot . 'elgg_copy/import/mod.zip')) {
        error_log("[elgg_copy] Could not retrieve mod");
        register_error("[elgg_copy] Could not retrieve mod");
        exit;
    }

    exec("cd {$dataroot}elgg_copy/import; unzip mod.zip;");
    exec("rm {$dataroot}elgg_copy/import/mod.zip; rm -rf {$dataroot}elgg_copy/import/elgg_copy;");
    error_log('[elgg_copy] deleting existing mod directory');
    $files = glob($plugins_path . '*', GLOB_MARK);
    foreach ($files as $file) {
        if (strpos(basename($file), 'elgg_copy') !== 0) {
            error_log('[elgg_copy] deleting ' . $file);
            exec("chmod -R 777 {$file}; rm -rf {$file}");
            if (is_dir($file) || is_file($file)) {
                error_log('[elgg_copy] could not delete ' . $file);
            }
        }
    }

    // move the dataroot back into place
    error_log('[elgg_copy] moving mod into place');
    exec("mv {$dataroot}elgg_copy/import/* {$plugins_path}; rm -rf {$dataroot}elgg_copy/import/*;");
    $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($plugins_path), \RecursiveIteratorIterator::SELF_FIRST); 

    foreach($iterator as $item) { 
        chmod($item, 0777); 
    } 
}

if ($update_database) {
    // get our settings
    error_log('[elgg_copy] fetching remote settings');
    $curl = "cd {$dataroot}elgg_copy/import; curl -fsS -m 7200 {$master_url}elgg_copy/settings/{$master_request_key} -o settings.json";
    exec($curl);

    if (!is_file($dataroot . 'elgg_copy/import/settings.json') || !filesize($dataroot . 'elgg_copy/import/settings.json')) {
        error_log('[elgg_copy] Could not fetch settings');
        register_error('[elgg_copy] Could not fetch settings');
        exit;
    }

    // get our database
    error_log('[elgg_copy] fetching remote database - this may take a while');
    $curl = "cd {$dataroot}elgg_copy/import; curl -fsS -m 7200 {$master_url}elgg_copy/database/{$master_request_key} -o database.sql.gz";
    exec($curl);

    if (!is_file($dataroot . 'elgg_copy/import/database.sql.gz') || !filesize($dataroot . 'elgg_copy/import/database.sql.gz')) {
        error_log('[elgg_copy] Could not fetch database');
        register_error('[elgg_copy] Could not fetch database');
        exit;
    }

    error_log('[elgg_copy] Unzipping database');
    exec("gunzip {$dataroot}elgg_copy/import/database.sql.gz");

    // Update the database
    $escaped_url = str_replace('/', '\/', rtrim($url, '/'));
    $escaped_master_url = str_replace('/', '\/', rtrim($master_url, '/'));

    // Update links
    error_log("[elgg_copy] Updating links");
    exec("/usr/bin/sed -i 's/{$escaped_master_url}/{$escaped_url}/g' {$dataroot}elgg_copy/import/database.sql");

    // Restore database
    error_log("[elgg_copy] Restoring database - this could take a while");
    exec("{$mysqldump_path}mysql -u{$dbuser} -p'{$dbpass}' -h {$dbhost} {$dbname} < {$dataroot}elgg_copy/import/database.sql");

    // Update site settings
    error_log("[elgg_copy] Updating dataroot and path");
    update_data("UPDATE {$dbprefix}datalists SET value = '{$dataroot}' where name = 'dataroot'");
    update_data("UPDATE {$dbprefix}datalists SET value = '{$path}' where name = 'path'");
    update_data("UPDATE {$dbprefix}sites_entity SET url = '{$url}' where guid = 1");
    update_data("UPDATE {$dbprefix}metastrings SET string = '{$dataroot}'
WHERE id = (
   SELECT value_id
   FROM {$dbprefix}metadata
   WHERE name_id = (
      SELECT *
      FROM (
         SELECT id
         FROM {$dbprefix}metastrings
         WHERE string = 'filestore::dir_root'
      ) as ms2
   )
   LIMIT 1
)");
		 
	// turn off https login if necessary
	if (strpos($url, 'https') === false) {
		update_data("UPDATE {$dbprefix}config SET value = 'i:0;' where name = 'https_login'");
	}
}

// Invalidate cache
error_log("[elgg_copy] Invalidating cache");
elgg_invalidate_simplecache();
elgg_reset_system_cache();

_elgg_invalidate_cache_for_entity($elgg_copy_plugin->guid);
_elgg_invalidate_memcache_for_entity($elgg_copy_plugin->guid);
_elgg_disable_caching_for_entity($elgg_copy_plugin->guid);
// unset plugins cache
elgg_set_config('plugins_by_id_map', array());

// regenerate plugin entities
error_log("[elgg_copy] Regenerating plugin entities");
_elgg_generate_plugin_entities();


// Update the sandbox settings
error_log("[elgg_copy] Updating elgg_copy plugin settings");
elgg_set_plugin_setting('master_url', $master_url, PLUGIN_ID);
elgg_set_plugin_setting('master_request_key', $master_request_key, PLUGIN_ID);
elgg_set_plugin_setting('request_key', $key, PLUGIN_ID);
elgg_set_plugin_setting('enable_plugins', $enable_plugins, PLUGIN_ID);
elgg_set_plugin_setting('update_mod', $update_mod, PLUGIN_ID);
elgg_set_plugin_setting('mysqldump_path', $mysqldump_path, PLUGIN_ID);
elgg_set_plugin_setting('update_dataroot', $update_dataroot, PLUGIN_ID);
elgg_set_plugin_setting('update_mod', $update_mod, PLUGIN_ID);
elgg_set_plugin_setting('update_database', $update_database, PLUGIN_ID);
elgg_set_plugin_setting('last_reset', date('Y-m-d g:ia'), PLUGIN_ID);

// Get and activate the dev_emails plugin
error_log("[elgg_copy] Enabling the dev plugins");
if ($enable_plugins) {
    $array = explode("\n", $enable_plugins);
    foreach ($array as $id) {
        $plugin = elgg_get_plugin_from_id(trim($id));
        if ($plugin) {
            $plugin->activate();
        }
    }
}

error_log("[elgg_copy] Updating site settings");
$site = elgg_get_site_entity();
$site->name = $site_name;
$site->email = $site_email;
$site->save();

exit;
