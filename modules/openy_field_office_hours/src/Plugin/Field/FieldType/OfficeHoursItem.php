<?php

namespace Drupal\ymca_field_office_hours\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Plugin implementation of the 'openy_office_hours' field type.
 *
 * @FieldType(
 *   id = "openy_office_hours",
 *   label = @Translation("OpenY Office Hours"),
 *   description = @Translation("Stores OpenY office hours."),
 *   default_widget = "openy_office_hours_default",
 *   default_formatter = "openy_office_hours"
 * )
 */
class OfficeHoursItem extends FieldItemBase implements FieldItemInterface {

  /**
   * Days of week.
   *
   * @var array
   */
  static public $days = ['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun'];

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties = [];

    foreach (self::$days as $day) {
      $properties['hours_' . $day] = DataDefinition::create('string')
        ->setLabel(t('Hours for %day', array('%day' => $day)));
    }

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    $schema = [];

    foreach (self::$days as $day) {
      $schema['columns']['hours_' . $day] = [
        'description' => sprintf('Hours for %s', ucfirst($day)),
        'type' => 'varchar',
        'length' => 255,
        'not null' => FALSE,
      ];
    }

    return $schema;
  }

}
