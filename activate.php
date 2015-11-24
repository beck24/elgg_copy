<?php

namespace MBeckett\ElggCopy;

$key = elgg_get_plugin_setting('request_key', PLUGIN_ID);
if (!$key) {
	$key = generate_key();
	elgg_set_plugin_setting('request_key', $key, PLUGIN_ID);
}