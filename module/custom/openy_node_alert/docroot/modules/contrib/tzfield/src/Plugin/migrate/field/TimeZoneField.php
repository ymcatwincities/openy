<?php

namespace Drupal\tzfield\Plugin\migrate\field;

use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate_drupal\Plugin\migrate\field\FieldPluginBase;

/**
 * Migration plugin for time zone field (tzfield).
 *
 * @MigrateField(
 *   id = "tzfield",
 *   core = {7},
 *   type_map = {
 *     "tzfield" = "tzfield"
 *   },
 *   source_module = "tzfield",
 *   destination_module = "tzfield"
 * )
 */
class TimeZoneField extends FieldPluginBase {

  /**
   * {@inheritdoc}
   */
  public function getFieldFormatterMap() {
    return [
      'tzfield_default' => 'basic_string',
      'tzfield_date' => 'basic_string',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldWidgetMap() {
    // By default, use the plugin ID for the widget types.
    return [
      'options_select' => 'tzfield_default',
      'tzfield_autocomplete' => 'tzfield_default',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function processFieldValues(MigrationInterface $migration, $field_name, $data) {
    $process = [
      'plugin' => 'sub_process',
      'source' => $field_name,
      'process' => [
        'value' => 'value',
      ],
    ];
    $migration->setProcessOfProperty($field_name, $process);
  }

}
