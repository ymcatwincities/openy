<?php

/**
 * @file
 * Administrative Drush commands for Purge - Command File.
 */

use Drupal\purge\Plugin\Purge\Invalidation\InvalidationsServiceInterface;
use Drupal\purge\Plugin\Purge\Purger\PurgersServiceInterface;

/**
 * List all supported cache invalidation types.
 *
 * @param string $format
 *   The default or selected output format of the command.
 * @param \Drupal\purge\Plugin\Purge\Invalidation\InvalidationsServiceInterface $purgeInvalidationFactory
 *   The purge invalidation factory service.
 * @param \Drupal\purge\Plugin\Purge\Purger\PurgersServiceInterface $purgePurgers
 *   The purge purgers service.
 *
 * @return array
 *   Drush render/format array.
 */
function purge_drush_p_types($format, InvalidationsServiceInterface $purgeInvalidationFactory, PurgersServiceInterface $purgePurgers) {
  $types_by_purger = $purgePurgers->getTypesByPurger();
  $types = $purgePurgers->getTypes();
  $output = [];

  // Return a simple listing of supported types.
  if ($format == 'list') {
    foreach ($types as $type) {
      $output[] = $type;
    }
    $output[] = '';
  }

  // Return a complexer data structure that can be rendered as table/yaml/json.
  elseif (in_array($format, ['table', 'yaml', 'json'])) {
    foreach ($purgeInvalidationFactory->getPlugins() as $type => $typedef) {
      $output[$type] = [
        'label' => (string) $typedef['label'],
        'supported' => in_array($type, $types),
      ];

      foreach ($purgePurgers->getPluginsEnabled() as $id => $plugin_id) {
        $output[$type][$id] = in_array($type, $types_by_purger[$id]);
        if ($format === 'table') {
          $output[$type][$id] = $output[$type][$id] ? '[X]' : '';
        }
      }
    }
  }

  return $output;
}
