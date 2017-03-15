<?php

namespace Drupal\openy_field_holiday_hours\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Plugin implementation of the 'openy_holiday_hours' field type.
 *
 * @FieldType(
 *   id = "openy_holiday_hours",
 *   label = @Translation("OpenY Holiday Hours"),
 *   description = @Translation("Stores Openy holiday hours."),
 *   default_widget = "openy_holiday_hours_default",
 *   default_formatter = "openy_holiday_hours"
 * )
 */
class HolidayHoursItem extends FieldItemBase implements FieldItemInterface {

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['holiday'] = DataDefinition::create('string')
      ->setLabel(t('Holiday title'));

    $properties['hours'] = DataDefinition::create('string')
      ->setLabel(t('Holiday hours'));

    $properties['date'] = DataDefinition::create('string')
      ->setLabel(t('Holiday date'));

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    $schema = [];

    $schema['columns']['holiday'] = [
      'description' => 'Holiday title.',
      'type' => 'varchar',
      'length' => 255,
      'not null' => TRUE,
    ];

    $schema['columns']['hours'] = [
      'description' => 'Holiday hours.',
      'type' => 'varchar',
      'length' => 255,
      'not null' => TRUE,
    ];

    $schema['columns']['date'] = [
      'type' => 'int',
      'description' => 'A unix timestamp indicating the date.',
      'not null' => TRUE,
    ];

    return $schema;
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $values = $this->getValue();
    if ($values['holiday'] !== '' && $values['hours'] !== '') {
      return FALSE;
    }
    return TRUE;
  }

}
