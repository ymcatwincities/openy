<?php

namespace Drupal\openy_migrate\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Get ClockWorks asset path.
 *
 * Accepts filename as a source value. Example: "2fs2df.jpg".
 *
 * @MigrateProcessPlugin(
 *   id = "clockworks_asset_path"
 * )
 */
class ClockWorksAssetPath extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    list($key, $extension) = explode('.', $value);
    $path = sprintf('%s/%s/%s.%s', $key[0], $key, $key, $extension);
    return $path;
  }

}
