<?php

/**
 * @file
 * Administrative Drush commands for Purge - Command File.
 */

use Drupal\purge\Plugin\Purge\Processor\ProcessorsServiceInterface;

/**
 * List all enabled processors.
 *
 * @param string $format
 *   The default or selected output format of the command.
 * @param \Drupal\purge\Plugin\Purge\Processor\ProcessorsServiceInterface $purgeProcessors
 *   The purge processors service.
 *
 * @return array
 *   Drush render/format array.
 */
function purge_drush_p_processors($format, ProcessorsServiceInterface $purgeProcessors) {
  $output = [];
  if (in_array($format, ['table', 'yaml', 'json'])) {
    foreach ($purgeProcessors as $processor) {
      $output[] = [
        'id' => (string) $processor->getPluginId(),
        'label' => (string) $processor->getLabel(),
        'description' => (string) $processor->getDescription(),
      ];
    }
  }
  elseif ($format == 'list') {
    foreach ($purgeProcessors as $processor) {
      $output[] = $processor->getPluginId();
    }
    $output[] = '';
  }
  return $output;
}
