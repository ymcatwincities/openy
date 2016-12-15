<?php

/**
 * @file
 * Administrative Drush commands for Purge - Command File.
 */

use Drupal\purge\Plugin\Purge\Purger\PurgersServiceInterface;

/**
 * List all plugin IDs for which purgers can be created.
 *
 * @param string $format
 *   The default or selected output format of the command.
 * @param \Drupal\purge\Plugin\Purge\Purger\PurgersServiceInterface $purgePurgers
 *   The purge purgers service.
 *
 * @return array
 *   Drush render/format array.
 */
function purge_drush_p_purgers_available($format, PurgersServiceInterface $purgePurgers) {
  $definitions = $purgePurgers->getPlugins();
  $available = $purgePurgers->getPluginsAvailable();
  $output = [];

  // Return a simple listing of each purger plugin ID.
  if ($format == 'list') {
    foreach ($available as $plugin_id) {
      $output[] = $plugin_id;
    }
    $output[] = '';
  }

  // Return a complexer data structure that can be rendered as table/yaml/json.
  elseif (in_array($format, ['table', 'yaml', 'json'])) {
    foreach ($available as $plugin_id) {
      $output[$plugin_id] = [
        'plugin_id' => $plugin_id,
        'label' => (string) $definitions[$plugin_id]['label'],
        'description' => (string) $definitions[$plugin_id]['description'],
      ];
    }
  }

  return $output;
}
