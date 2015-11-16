<?php

/**
 * @file
 * Contains \Drupal\ymca_migrate\Plugin\migrate\process\YmcaMigrateDate.
 */

namespace Drupal\ymca_migrate\Plugin\migrate\process;

use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\MigrateExecutableInterface;
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
    $date = \DateTime::createFromFormat('Y-m-d H:i:s', $value, new \DateTimeZone('UTC'));
    return $date->getTimestamp();
  }

}
