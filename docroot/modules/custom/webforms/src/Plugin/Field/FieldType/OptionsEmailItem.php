<?php

/**
 * @file
 * Contains field type definition.
 */

namespace Drupal\webforms\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
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

  /**
   * {@inheritdoc}
   */
  public function storageSettingsForm(array &$form, FormStateInterface $form_state, $has_data) {
    $allowed_values = $this->getSetting('allowed_values');
    $allowed_values_function = $this->getSetting('allowed_values_function');

    $element['allowed_values'] = array(
      '#type' => 'textarea',
      '#title' => t('Allowed values list'),
      '#default_value' => $allowed_values,
      '#rows' => 10,
      '#field_has_data' => $has_data,
      '#field_name' => $this->getFieldDefinition()->getName(),
      '#entity_type' => $this->getEntity()->getEntityTypeId(),
      '#allowed_values' => $allowed_values,
    );

    $element['allowed_values_function'] = array(
      '#type' => 'item',
      '#title' => t('Allowed values list'),
      '#markup' => t('The value of this field is being determined by the %function function and may not be changed.', array('%function' => $allowed_values_function)),
      '#access' => !empty($allowed_values_function),
      '#value' => $allowed_values_function,
    );

    return $element;
  }

}
