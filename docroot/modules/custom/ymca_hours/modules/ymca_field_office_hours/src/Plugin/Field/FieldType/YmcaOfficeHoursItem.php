<?php

/**
 * @file
 * Contains field type definition.
 */

namespace Drupal\ymca_field_office_hours\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Plugin implementation of the 'ymca_office_hours' field type.
 *
 * @FieldType(
 *   id = "ymca_office_hours",
 *   label = @Translation("YMCA office hours"),
 *   description = @Translation("Stores YMCA office hours."),
 *   default_widget = "ymca_office_hours_default",
 *   default_formatter = "ymca_office_hours"
 * )
 */
class YmcaOfficeHoursItem extends FieldItemBase implements FieldItemInterface {

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['value'] = DataDefinition::create('any')
      ->setLabel(t('Hours'));

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return array(
      'columns' => array(
        'value' => array(
          'description' => 'Serialized hours value.',
          'type' => 'text',
          'size' => 'tiny',
          'not null' => FALSE,
        ),
      ),
    );
  }

}
