<?php

namespace Arck\ElggCopy;

set_time_limit(7200); // 2 hours

elgg_set_ignore_access(true);

$dataroot        = elgg_get_config('dataroot');
$path            = elgg_get_config('path');
$dbuser          = elgg_get_config('dbuser');
$dbpass          = elgg_get_config('dbpass');
$dbhost          = elgg_get_config('dbhost');
$dbname          = elgg_get_config('dbname');
$dbprefix        = elgg_get_config('dbprefix');
$url             = elgg_get_site_url();

$elgg_copy_plugin  = elgg_get_plugin_from_id(PLUGIN_ID);
$master_url      = elgg_get_plugin_setting('master_url', PLUGIN_ID);
$master_path     = elgg_get_plugin_setting('master_path', PLUGIN_ID);
$master_dataroot = elgg_get_plugin_setting('master_dataroot', PLUGIN_ID);
$site_name       = elgg_get_plugin_setting('site_name', PLUGIN_ID);
$site_email      = elgg_get_plugin_setting('site_email', PLUGIN_ID);
$enable_plugins  = elgg_get_plugin_setting('enable_plugins', PLUGIN_ID);
$sync_plugins    = elgg_get_plugin_setting('sync_plugins', PLUGIN_ID);

$settings = file($master_path . 'engine/settings.php');

foreach ($settings as $setting) {

    if (preg_match("/CONFIG->dbuser\s+=\s+'(\S+)'/", $setting, $matches)) {
        $master_dbuser = $matches[1];
    } else if (preg_match("/CONFIG->dbpass\s+=\s+'(\S+)'/", $setting, $matches)) {
        $master_dbpass = $matches[1];
    } else if (preg_match("/CONFIG->dbname\s+=\s+'(\S+)'/", $setting, $matches)) {
        $master_dbname = $matches[1];
    } else if (preg_match("/CONFIG->dbhost\s+=\s+'(\S+)'/", $setting, $matches)) {
        $master_dbhost = $matches[1];
    }
}

// make sure we have required info before proceeding
if (!$master_url || !$master_path || !$master_dataroot || !$site_name || !$site_email) {
    register_error("Missing required information - please check the elgg_copy plugin settings");
    forward(REFERER);
}

// Dump the master database
error_log("[action/elgg_copy/reset] Dumping master database");
$dump = "/usr/bin/mysqldump -u{$master_dbuser} -p'{$master_dbpass}' -h {$master_dbhost} {$master_dbname} --ignore-table={$master_dbname}.elgg_users_sessions > {$dataroot}{$master_dbname}.sql";
exec($dump);

// Update the database
$url_parts = explode('://', $url);
$url_domain = str_replace('/', '\/', rtrim($url_parts[1], '/'));
if (strpos($master_url, 'http') === 0) {
    // strip off the protocol
    $parts = explode('://', $master_url);
    $master_domain = str_replace('/', '\/', rtrim($parts[1], '/'));
} else {
    $master_domain = str_replace('/', '\/',$master_url); // not sure if this will work...
}

// Update links
error_log("[action/elgg_copy/reset] Updating links");
exec("/usr/bin/sed -i 's/{$master_domain}/{$url_domain}/g' {$dataroot}{$master_dbname}.sql");

// Normalize www
//exec("/usr/bin/sed -i 's/www.elgg_copy/elgg_copy/g' {$dataroot}{$master_dbname}.sql");

// Sync master data directory
error_log("[action/elgg_copy/reset] Syncing data directory");
exec("/usr/bin/rsync -ar --exclude={$master_dbname}.sql --exclude=system_cache --exclude=views_simplecache --delete {$master_dataroot} {$dataroot}");

// Sync master document root
if ($sync_plugins) {
    error_log("[action/elgg_copy/reset] Syncing plugins directory");
    exec("/usr/bin/rsync -ar --exclude=elgg_copy --delete {$master_path}mod/ {$path}mod/");
}

// Restore database
error_log("[action/elgg_copy/reset] Restoring database");
exec("/usr/bin/mysql -u{$dbuser} -p'{$dbpass}' -h {$dbhost} {$dbname} < {$dataroot}{$master_dbname}.sql");

// Update site settings
error_log("[action/elgg_copy/reset] Updating dataroot and path");
update_data("UPDATE {$dbprefix}datalists SET value = '{$dataroot}' where name = 'dataroot'");
update_data("UPDATE {$dbprefix}datalists SET value = '{$path}' where name = 'path'");


// Invalidate cache
error_log("[action/elgg_copy/reset] Invalidating cache");
elgg_invalidate_simplecache();
elgg_reset_system_cache();
_elgg_invalidate_query_cache();
_elgg_invalidate_cache_for_entity($elgg_copy_plugin->guid);
_elgg_invalidate_memcache_for_entity($elgg_copy_plugin->guid);
_elgg_disable_caching_for_entity($elgg_copy_plugin->guid);
// unset plugins cache
elgg_set_config('plugins_by_id_map', array());


// regenerate plugin entities
error_log("[action/elgg_copy/reset] Regenerating plugin entities");
elgg_generate_plugin_entities();

// Get and activate the elgg_copy plugin
error_log("[action/elgg_copy/reset] Enabling the elgg_copy plugin");
$plugin = elgg_get_plugin_from_id('elgg_copy');
if ($plugin) {
    // not sure how we couldn't find this plugin...
    $plugin->activate();
} else {
    error_log('could not find the elgg_copy plugin...');
}

// Update the elgg_copy settings
error_log("[action/elgg_copy/reset] Updating elgg_copy plugin settings");
elgg_set_plugin_setting('site_name', $site_name, 'elgg_copy');
elgg_set_plugin_setting('site_email', $site_email, 'elgg_copy');
elgg_set_plugin_setting('master_url', $master_url, 'elgg_copy');
elgg_set_plugin_setting('master_path', $master_path, 'elgg_copy');
elgg_set_plugin_setting('master_dataroot', $master_dataroot, 'elgg_copy');
elgg_set_plugin_setting('enable_plugins', $enable_plugins, 'elgg_copy');
elgg_set_plugin_setting('sync_plugins', $sync_plugins, 'elgg_copy');
elgg_set_plugin_setting('last_reset', date('Y-m-d h:i'), 'elgg_copy');

// Get and activate the dev_emails plugin
error_log("[action/elgg_copy/reset] Enabling the dev plugins");
if ($enable_plugins) {
    $array = explode("\n", $enable_plugins);
    foreach ($array as $id) {
        $plugin = elgg_get_plugin_from_id(trim($id));
        if ($plugin) {
            $plugin->activate();
        }
    }
}

error_log("[action/elgg_copy/reset] Updating site settings");
$site = elgg_get_site_entity();
$site->name = $site_name; //'Griffin Groups Sandbox';
$site->email = $site_email; //'site@elgg_copy.griffingroups.com';
$site->save();

exit;
