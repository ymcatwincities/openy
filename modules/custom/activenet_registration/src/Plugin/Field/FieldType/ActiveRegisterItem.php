<?php

namespace Drupal\activenet_registration\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Plugin implementation for activenet_registration field type.
 *
 * @FieldType(
 *   id = "activenet_registration",
 *   label = @Translation("ActiveNet Registration"),
 *   description = @Translation("ActiveNet Registration fields."),
 *   default_formatter = "activenet_registration_formatter",
 *   default_widget = "activenet_registration_widget",
 * )
 */
class ActiveRegisterItem extends FieldItemBase {
  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {

    $properties['activity_flex'] = DataDefinition::create('string')
      ->setlabel(t('Activity/FlexReg'));

    $properties['activity_name'] = DataDefinition::create('string')
      ->setlabel(t('Activity/Program Name'));

    // properites are all initiated as strings, due to a bug that won't allow null integers, https://www.drupal.org/project/drupal/issues/2925445#comment-12539383 
    $properties['site'] = DataDefinition::create('string')
      ->setLabel(t('Site'));

    $properties['program_type'] = DataDefinition::create('string')
      ->setLabel(t('Program Type'));

    $properties['activity_type'] = DataDefinition::create('string')
      ->setLabel(t('Activity Type'));

    $properties['category'] = DataDefinition::create('string')
      ->setLabel(t('Category'));

    $properties['other_category'] = DataDefinition::create('string')
      ->setLabel(t('Other Category'));

    $properties['gender'] = DataDefinition::create('string')
      ->setLabel(t('Gender'));

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {

    $schema['columns']['activity_flex'] = [
      'description' => 'Activity or Flex Registration type',
      'type' => 'varchar',
      'length' => 255,
    ];

    $schema['columns']['site'] = [
      'description' => 'Site ID',
      'type' => 'int',
    ];

    $schema['columns']['activity_name'] = [
      'description' => 'Activity Name',
      'type' => 'varchar',
      'length' => 255,
    ];

    $schema['columns']['program_type'] = [
      'description' => 'Program type ID',
      'type' => 'int',
    ];

    $schema['columns']['activity_type'] = [
      'description' => 'Program type ID',
      'type' => 'int',
    ];

    $schema['columns']['category'] = [
      'description' => 'Category ID',
      'type' => 'int',
    ];

    $schema['columns']['other_category'] = [
      'description' => 'Other Category ID',
      'type' => 'int',
    ];

    $schema['columns']['gender'] = [
      'description' => 'Gender ID',
      'type' => 'int',
    ];

    return $schema;
  }

}
