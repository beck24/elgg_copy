<?php

namespace Arck\ElggCopy;

$key = generate_key();

elgg_set_plugin_setting('request_key', $key, PLUGIN_ID);

system_message('Request Key has been regenerated');

forward(REFERER);
