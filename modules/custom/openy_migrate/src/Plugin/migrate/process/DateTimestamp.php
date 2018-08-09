<?php

namespace Drupal\openy_migrate\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\MigrateException;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 *  Migrage process plugin date(to_format, timestamp);
 *
 * @MigrateProcessPlugin(
 *   id = "date_timestamp"
 * )
 */
class dateTimestamp extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {

    // Validate the configuration.
    if (empty($this->configuration['to_format'])) {
      throw new MigrateException('Date Timestamp plugin is missing to_format configuration.');
    }

    $to_format = $this->configuration['to_format'];

    $date = date($to_format, $value);
    return $date;
  }

}
