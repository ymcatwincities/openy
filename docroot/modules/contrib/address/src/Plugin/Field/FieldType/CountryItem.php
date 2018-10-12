<?php

namespace Drupal\address\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\Field\FieldStorageDefinitionInterface;

/**
 * Plugin implementation of the 'address_country' field type.
 *
 * @FieldType(
 *   id = "address_country",
 *   label = @Translation("Country"),
 *   description = @Translation("An entity field containing a country"),
 *   category = @Translation("Address"),
 *   default_widget = "address_country_default",
 *   default_formatter = "address_country_default"
 * )
 */
class CountryItem extends FieldItemBase {

  use AvailableCountriesTrait;

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return [
      'columns' => [
        'value' => [
          'type' => 'char',
          'length' => 2,
          'not null' => FALSE,
        ],
      ],
      'indexes' => [
        'value' => ['value'],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['value'] = DataDefinition::create('string')
      ->setLabel(t('Country'));
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
    return empty($this->value);
  }

  /**
   * {@inheritdoc}
   */
  public function getConstraints() {
    $constraints = parent::getConstraints();
    $constraint_manager = \Drupal::typedDataManager()->getValidationConstraintManager();
    $constraints[] = $constraint_manager->create('ComplexData', [
      'value' => [
        'Country' => [
          'availableCountries' => $this->getAvailableCountries(),
        ],
      ],
    ]);

    return $constraints;
  }

}
