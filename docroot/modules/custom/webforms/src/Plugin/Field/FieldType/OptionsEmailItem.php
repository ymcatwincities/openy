<?php

namespace Drupal\webforms\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Plugin implementation of the 'ymca_office_hours' field type.
 *
 * @FieldType(
 *   id = "options_email_item",
 *   label = @Translation("Options email item"),
 *   description = @Translation("Stores options email."),
 *   default_widget = "options_email_default",
 *   default_formatter = "options_emails_formatter"
 * )
 */
class OptionsEmailItem extends FieldItemBase implements FieldItemInterface {

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(
    FieldStorageDefinitionInterface $field_definition
  ) {
    $properties = [];

    $properties['option_name'] = DataDefinition::create('string')
      ->setLabel(t('Option name'));
    $properties['option_reference'] = DataDefinition::create('string')
      ->setLabel(t('Option reference'));
    $properties['option_emails'] = DataDefinition::create('string')
      ->setLabel(t('Option emails'));

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(
    FieldStorageDefinitionInterface $field_definition
  ) {
    $schema = [];

    $schema['columns']['option_name'] = [
      'description' => t('Option name'),
      'type' => 'varchar',
      'length' => 255,
      'not null' => FALSE,
    ];
    $schema['columns']['option_emails'] = [
      'description' => t('Option emails'),
      'type' => 'varchar',
      'length' => 1024,
      'not null' => FALSE,
    ];

    return $schema;
  }

}
