<?php

namespace Drupal\ymca_migrate\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Process the date to timestamp.
 *
 * @MigrateProcessPlugin(
 *   id = "ymca_migrate_date"
 * )
 */
class YmcaMigrateDate extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    // Site wide Timezone settings is currently doesn't work, so use custom config.
    $date = \DateTime::createFromFormat(
      'Y-m-d H:i:s',
      $value,
      new \DateTimeZone(
        \Drupal::config('system.date')->get('timezone')['default']
      )
    );
    return $date->getTimestamp();
  }

}
