<?php

/**
 * @file
 * Administrative Drush commands for Purge - Command File.
 */

use Drupal\purge\Plugin\Purge\Queuer\QueuersServiceInterface;

/**
 * List all enabled queuers.
 *
 * @param string $format
 *   The default or selected output format of the command.
 * @param \Drupal\purge\Plugin\Purge\Queuer\QueuersServiceInterface $purgeQueuers
 *   The purge queuers service.
 *
 * @return array
 *   Drush render/format array.
 */
function purge_drush_p_queuers($format, QueuersServiceInterface $purgeQueuers) {
  $output = [];
  if (in_array($format, ['table', 'yaml', 'json'])) {
    foreach ($purgeQueuers as $queuer) {
      $output[] = [
        'id' => (string) $queuer->getPluginId(),
        'label' => (string) $queuer->getLabel(),
        'description' => (string) $queuer->getDescription(),
      ];
    }
  }
  elseif ($format == 'list') {
    foreach ($purgeQueuers as $queuer) {
      $output[] = $queuer->getPluginId();
    }
    $output[] = '';
  }
  return $output;
}
