<?php

namespace Drupal\openy_gxp\Plugin\migrate\process;

use Drupal\migrate\Annotation\MigrateProcessPlugin;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Parse time and build proper Exclusions properties on Session fields.
 *
 * @MigrateProcessPlugin(
 *   id = "openy_gxp_exclusions"
 * )
 */
class OpenYGXPExclusions extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $value = json_decode($value, TRUE);
    if (empty($value)) {
      return;
    }

    return $value;
  }

  /**
   * {@inheritdoc}
   */
  public function multiple() {
    return TRUE;
  }

}
