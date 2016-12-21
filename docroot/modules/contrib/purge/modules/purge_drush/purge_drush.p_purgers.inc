<?php

/**
 * @file
 * Administrative Drush commands for Purge - Command File.
 */

use Drupal\purge\Plugin\Purge\Purger\PurgersServiceInterface;

/**
 * List all configured purgers in order of execution.
 *
 * @param string $format
 *   The default or selected output format of the command.
 * @param \Drupal\purge\Plugin\Purge\Purger\PurgersServiceInterface $purgePurgers
 *   The purge purgers service.
 *
 * @return array
 *   Drush render/format array.
 */
function purge_drush_p_purgers($format, PurgersServiceInterface $purgePurgers) {
  $definitions = $purgePurgers->getPlugins();
  $enabled = $purgePurgers->getPluginsEnabled();
  $labels = $purgePurgers->getLabels();
  $output = [];

  // Return a simple listing of each purger instance ID.
  if ($format == 'list') {
    foreach ($enabled as $instance_id => $plugin_id) {
      $output[] = $instance_id;
    }
    $output[] = '';
  }

  // Return a complexer data structure that can be rendered as table/yaml/json.
  elseif (in_array($format, ['table', 'yaml', 'json'])) {
    foreach ($enabled as $instance_id => $plugin_id) {
      $output[$instance_id] = [
        'instance_id' => $instance_id,
        'plugin_id' => $plugin_id,
        'label' => (string) $labels[$instance_id],
        'description' => (string) $definitions[$plugin_id]['description'],
      ];
    }
  }

  return $output;
}
