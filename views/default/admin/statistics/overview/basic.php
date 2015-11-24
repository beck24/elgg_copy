<?php

namespace Mbeckett\ElggCopy;

// Work out number of users
$users_stats = get_number_users();
$total_users = get_number_users(true);

// Get version information
$version = get_version();
$release = get_version(true);

$last_reset = elgg_get_plugin_setting('last_reset', PLUGIN_ID);
$last_reset = $last_reset ? $last_reset : 'Never';

?>

<table class="elgg-table-alt">
	<tr class="odd">
		<td><b><?php echo elgg_echo('admin:statistics:label:version'); ?> :</b></td>
		<td><?php echo elgg_echo('admin:statistics:label:version:release'); ?> - <?php echo $release; ?>, <?php echo elgg_echo('admin:statistics:label:version:version'); ?> - <?php echo $version; ?></td>
	</tr>
	<tr class="even">
		<td><b><?php echo elgg_echo('admin:statistics:label:numusers'); ?> :</b></td>
		<td><?php echo $users_stats; ?> <?php echo elgg_echo('active'); ?> / <?php echo $total_users; ?> <?php echo elgg_echo('total') ?></td>
	</tr>
	<tr class="odd">
		<td><b>Last reset :</b></td>
		<td><?php echo $last_reset; ?></td>
	</tr>
</table>
