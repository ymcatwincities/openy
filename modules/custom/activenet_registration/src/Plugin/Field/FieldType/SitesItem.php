<?php

namespace Drupal\activenet_registration\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Plugin implementation for activenet_sites field type.
 *
 * @FieldType(
 *   id = "activenet_sites",
 *   label = @Translation("Activenet Sites"),
 *   description = @Translation("Provides sites selection from Activenet."),
 *   default_formatter = "activenet_sites_formatter",
 *   default_widget = "activenet_sites_widget",
 * )
 */
class SitesItem extends FieldItemBase {
  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {

    // properites are all initiated as strings, due to a bug that won't allow null integers, https://www.drupal.org/project/drupal/issues/2925445#comment-12539383 
    $properties['site'] = DataDefinition::create('string')
      ->setLabel(t('Site'));

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {

    $schema['columns']['site'] = [
      'description' => 'Site ID',
      'type' => 'int',
    ];

    return $schema;
  }

}
