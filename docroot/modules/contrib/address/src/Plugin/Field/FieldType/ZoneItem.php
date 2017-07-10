<?php

namespace Drupal\address\Plugin\Field\FieldType;

use CommerceGuys\Addressing\Zone\Zone;
use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Plugin implementation of the 'zone' field type.
 *
 * @FieldType(
 *   id = "address_zone",
 *   label = @Translation("Zone"),
 *   description = @Translation("An entity field containing a zone"),
 *   category = @Translation("Address"),
 *   list_class = "\Drupal\address\Plugin\Field\FieldType\ZoneItemList",
 *   default_widget = "address_zone_default",
 *   cardinality = 1,
 * )
 */
class ZoneItem extends FieldItemBase {

  use AvailableCountriesTrait;

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return [
      'columns' => [
        'value' => [
          'description' => 'The serialized zone.',
          'type' => 'blob',
          'not null' => TRUE,
          'serialize' => TRUE,
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties = [];
    $properties['value'] = DataDefinition::create('any')
      ->setLabel(t('Value'))
      ->setRequired(TRUE);

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultFieldSettings() {
    return self::defaultCountrySettings();
  }

  /**
   * {@inheritdoc}
   */
  public function fieldSettingsForm(array $form, FormStateInterface $form_state) {
    return $this->countrySettingsForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    return $this->value === NULL || !$this->value instanceof Zone;
  }

  /**
   * {@inheritdoc}
   */
  public function setValue($values, $notify = TRUE) {
    if (is_array($values)) {
      // The property definition causes the zone to be in 'value' key.
      $values = reset($values);
    }
    if (!$values instanceof Zone) {
      $values = NULL;
    }
    parent::setValue($values, $notify);
  }

}
