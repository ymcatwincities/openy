<?php

namespace Drupal\openy_demo_menu_main\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Fills in link options with icon information.
 *
 * @MigrateProcessPlugin(
 *   id = "openy_menu_icon"
 * )
 */
class OpenYMenuIcon extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $options = [
      'menu_icon' => [
        'fid' => $value,
      ],
    ];
    return serialize($options);
  }

}
