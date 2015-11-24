<?php
	$regen = elgg_view('output/url', array(
		'text' => 'Regenerate',
		'href' => 'action/elgg_copy/regenerate_key',
		'is_action' => true,
		'confirm' => true
	));
?>
<div>
    <p>
    Request Key: <?php echo $vars['entity']->request_key; echo ' (' . $regen . ')';?>
    </p>
</div>

<div>
    <label class="label">Master URL:</label>
    <p><?php echo elgg_view('input/text', array('name' => 'params[master_url]', 'value' => $vars['entity']->master_url)); ?></p>
</div>

<div>
    <label class="label">Master Request Key:</label>
    <p><?php echo elgg_view('input/text', array('name' => 'params[master_request_key]', 'value' => $vars['entity']->master_request_key)); ?></p>
</div>

<div>
    <label class="label">Path to mysql/mysqldump on <b>THIS</b> server:</label>
    <p><?php echo elgg_view('input/text', array('name' => 'params[mysqldump_path]', 'value' => $vars['entity']->mysqldump_path)); ?></p>
	<div class="elgg-subtext">
		eg. /usr/bin/
	</div>
</div>

<p>NOTE: Be sure to include trailing slash on the paths.</p>

<div>
    <label class="label">Dev plugins to enable:</label>
    <p><?php echo elgg_view('input/plaintext', array('name' => 'params[enable_plugins]', 'value' => $vars['entity']->enable_plugins)); ?></p>
	<div class="elgg-subtext">
		List of plugins to enable after the sync - these should be plugins that are only to be enabled on dev eg. dev_emails<br>
		Enter one plugin ID per line
	</div>
</div>

<div>
    <p><?php
			$options = array('name' => 'params[update_mod]', 'value' => 1);
			if ($vars['entity']->update_mod) {
				$options['checked'] = 'checked';
			}
			echo '<label>' . elgg_view('input/checkbox', $options);
			echo ' Sync the plugins directory</label>';
		?></p>
</div>
<div>
    <p><?php
			$options = array('name' => 'params[update_dataroot]', 'value' => 1);
			if ($vars['entity']->update_dataroot) {
				$options['checked'] = 'checked';
			}
			echo '<label>' . elgg_view('input/checkbox', $options);
			echo ' Sync the data directory</label>';
		?></p>
</div>
<div>
    <p><?php
			$options = array('name' => 'params[update_database]', 'value' => 1);
			if ($vars['entity']->update_database) {
				$options['checked'] = 'checked';
			}
			echo '<label>' . elgg_view('input/checkbox', $options);
			echo ' Sync the database</label>';
		?></p>
</div>
