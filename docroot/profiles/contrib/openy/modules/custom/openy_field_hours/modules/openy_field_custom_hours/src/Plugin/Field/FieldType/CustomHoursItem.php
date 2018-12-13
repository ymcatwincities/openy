<?php

namespace Drupal\openy_field_custom_hours\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Plugin implementation for openy_custom_hours field type.
 *
 * @FieldType(
 *   id = "openy_custom_hours",
 *   label = @Translation("Open Y Custom Hours"),
 *   description = @Translation("Stores OpenY custom hours."),
 *   default_widget = "openy_custom_hours_default",
 *   default_formatter = "openy_custom_hours_default"
 * )
 */
class CustomHoursItem extends FieldItemBase implements FieldItemInterface {

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
    $properties['hours_label'] = DataDefinition::create('string')
      ->setLabel(t('Custom hours label'));

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
    $schema['columns']['hours_label'] = [
      'description' => 'Custom hours label.',
      'type' => 'varchar',
      'length' => 255,
      'not null' => FALSE,
    ];

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

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $values = $this->getValue();
    if ($values['hours_label'] !== '') {
      return FALSE;
    }
    return TRUE;
  }

}
