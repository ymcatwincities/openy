<?php

namespace Drupal\address\Plugin\Field\FieldType;

use CommerceGuys\Addressing\AddressFormat\AddressField;
use CommerceGuys\Addressing\AddressFormat\FieldOverride;
use CommerceGuys\Addressing\AddressFormat\FieldOverrides;
use Drupal\address\AddressInterface;
use Drupal\address\LabelHelper;
use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Plugin implementation of the 'address' field type.
 *
 * @FieldType(
 *   id = "address",
 *   label = @Translation("Address"),
 *   description = @Translation("An entity field containing a postal address"),
 *   category = @Translation("Address"),
 *   default_widget = "address_default",
 *   default_formatter = "address_default"
 * )
 */
class AddressItem extends FieldItemBase implements AddressInterface {

  use AvailableCountriesTrait;

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return [
      'columns' => [
        'langcode' => [
          'type' => 'varchar',
          'length' => 32,
        ],
        'country_code' => [
          'type' => 'varchar',
          'length' => 2,
        ],
        'administrative_area' => [
          'type' => 'varchar',
          'length' => 255,
        ],
        'locality' => [
          'type' => 'varchar',
          'length' => 255,
        ],
        'dependent_locality' => [
          'type' => 'varchar',
          'length' => 255,
        ],
        'postal_code' => [
          'type' => 'varchar',
          'length' => 255,
        ],
        'sorting_code' => [
          'type' => 'varchar',
          'length' => 255,
        ],
        'address_line1' => [
          'type' => 'varchar',
          'length' => 255,
        ],
        'address_line2' => [
          'type' => 'varchar',
          'length' => 255,
        ],
        'organization' => [
          'type' => 'varchar',
          'length' => 255,
        ],
        'given_name' => [
          'type' => 'varchar',
          'length' => 255,
        ],
        'additional_name' => [
          'type' => 'varchar',
          'length' => 255,
        ],
        'family_name' => [
          'type' => 'varchar',
          'length' => 255,
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function mainPropertyName() {
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties = [];
    $properties['langcode'] = DataDefinition::create('string')
      ->setLabel(t('The language code.'));
    $properties['country_code'] = DataDefinition::create('string')
      ->setLabel(t('The two-letter country code.'));
    $properties['administrative_area'] = DataDefinition::create('string')
      ->setLabel(t('The top-level administrative subdivision of the country.'));
    $properties['locality'] = DataDefinition::create('string')
      ->setLabel(t('The locality (i.e. city).'));
    $properties['dependent_locality'] = DataDefinition::create('string')
      ->setLabel(t('The dependent locality (i.e. neighbourhood).'));
    $properties['postal_code'] = DataDefinition::create('string')
      ->setLabel(t('The postal code.'));
    $properties['sorting_code'] = DataDefinition::create('string')
      ->setLabel(t('The sorting code.'));
    $properties['address_line1'] = DataDefinition::create('string')
      ->setLabel(t('The first line of the address block.'));
    $properties['address_line2'] = DataDefinition::create('string')
      ->setLabel(t('The second line of the address block.'));
    $properties['organization'] = DataDefinition::create('string')
      ->setLabel(t('The organization'));
    $properties['given_name'] = DataDefinition::create('string')
      ->setLabel(t('The given name.'));
    $properties['additional_name'] = DataDefinition::create('string')
      ->setLabel(t('The additional name.'));
    $properties['family_name'] = DataDefinition::create('string')
      ->setLabel(t('The family name.'));

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultFieldSettings() {
    return self::defaultCountrySettings() + [
      'langcode_override' => '',
      'field_overrides' => [],
      // Replaced by field_overrides.
      'fields' => [],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function fieldSettingsForm(array $form, FormStateInterface $form_state) {
    $languages = \Drupal::languageManager()->getLanguages(LanguageInterface::STATE_ALL);
    $language_options = [];
    foreach ($languages as $langcode => $language) {
      // Only list real languages (English, French, but not "Not specified").
      if (!$language->isLocked()) {
        $language_options[$langcode] = $language->getName();
      }
    }

    $element = $this->countrySettingsForm($form, $form_state);
    $element['langcode_override'] = [
      '#type' => 'select',
      '#title' => $this->t('Language override'),
      '#description' => $this->t('Ensures entered addresses are always formatted in the same language.'),
      '#options' => $language_options,
      '#default_value' => $this->getSetting('langcode_override'),
      '#empty_option' => $this->t('- No override -'),
      '#access' => \Drupal::languageManager()->isMultilingual(),
    ];

    $element['field_overrides_title'] = [
      '#type' => 'item',
      '#title' => $this->t('Field overrides'),
      '#description' => $this->t('Use field overrides to override the country-specific address format, forcing specific fields to always be hidden, optional, or required.'),
    ];
    $element['field_overrides'] = [
      '#type' => 'table',
      '#header' => [
        $this->t('Field'),
        $this->t('Override'),
      ],
      '#element_validate' => [[get_class($this), 'fieldOverridesValidate']],
    ];
    $field_overrides = $this->getFieldOverrides();
    foreach (LabelHelper::getGenericFieldLabels() as $field_name => $label) {
      $override = isset($field_overrides[$field_name]) ? $field_overrides[$field_name] : '';

      $element['field_overrides'][$field_name] = [
        'field_label' => [
          '#type' => 'markup',
          '#markup' => $label,
        ],
        'override' => [
          '#type' => 'select',
          '#options' => [
            FieldOverride::HIDDEN => t('Hidden'),
            FieldOverride::OPTIONAL => t('Optional'),
            FieldOverride::REQUIRED => t('Required'),
          ],
          '#default_value' => $override,
          '#empty_option' => $this->t('- No override -'),
        ],
      ];
    }

    return $element;
  }

  /**
   * Form element validation handler: Removes empty field overrides.
   *
   * @param array $element
   *   The field overrides form element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state of the entire form.
   */
  public static function fieldOverridesValidate(array $element, FormStateInterface $form_state) {
    $overrides = $form_state->getValue($element['#parents']);
    $overrides = array_filter($overrides, function ($data) {
      return !empty($data['override']);
    });
    $form_state->setValue($element['#parents'], $overrides);
  }

  /**
   * Gets the field overrides for the current field.
   *
   * @return array
   *   FieldOverride constants keyed by AddressField constants.
   */
  public function getFieldOverrides() {
    $field_overrides = [];
    if ($fields = $this->getSetting('fields')) {
      $unused_fields = array_diff(AddressField::getAll(), $fields);
      foreach ($unused_fields as $field) {
        $field_overrides[$field] = FieldOverride::HIDDEN;
      }
    }
    else {
      foreach ($this->getSetting('field_overrides') as $field => $data) {
        $field_overrides[$field] = $data['override'];
      }
    }

    return $field_overrides;
  }

  /**
   * Initializes and returns the langcode property for the current field.
   *
   * Some countries use separate address formats for the local language VS
   * other languages. For example, China uses major-to-minor ordering
   * when the address is entered in Chinese, and minor-to-major when the
   * address is entered in other languages.
   * This means that the address must remember which language it was
   * entered in, to ensure consistent formatting later on.
   *
   * - For translatable entities this information comes from the field langcode.
   * - Non-translatable entities have no way to provide this information, since
   *   the field langcode never changes. In this case the field must store
   *   the interface language at the time of address creation.
   * - It is also possible to override the used language via field settings,
   *   in case the language is always known (e.g. a field storing the "english
   *   address" on a chinese article).
   *
   * The langcode property is intepreted by getLocale(), and in case it's NULL,
   * the field langcode is returned instead (indicating a non-multilingual site
   * or a translatable parent entity).
   *
   * @return string|null
   *   The langcode, or NULL if the field langcode should be used instead.
   */
  public function initializeLangcode() {
    $this->langcode = NULL;
    $language_manager = \Drupal::languageManager();
    if (!$language_manager->isMultilingual()) {
      return;
    }

    if ($override = $this->getSetting('langcode_override')) {
      $this->langcode = $override;
    }
    elseif (!$this->getEntity()->isTranslatable()) {
      // The getCurrentLanguage fallback is a workaround for core bug #2684873.
      $language = $language_manager->getConfigOverrideLanguage() ?: $language_manager->getCurrentLanguage();
      $this->langcode = $language->getId();
    }

    return $this->langcode;
  }

  /**
   * {@inheritdoc}
   */
  public function getConstraints() {
    $constraints = parent::getConstraints();
    $constraint_manager = \Drupal::typedDataManager()->getValidationConstraintManager();
    $field_overrides = new FieldOverrides($this->getFieldOverrides());
    $constraints[] = $constraint_manager->create('ComplexData', [
      'country_code' => [
        'Country' => [
          'availableCountries' => $this->getAvailableCountries(),
        ],
      ],
    ]);
    $constraints[] = $constraint_manager->create('AddressFormat', ['fieldOverrides' => $field_overrides]);

    return $constraints;
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $value = $this->country_code;
    return $value === NULL || $value === '';
  }

  /**
   * {@inheritdoc}
   */
  public function getLocale() {
    $langcode = $this->langcode;
    if (!$langcode) {
      // If no langcode was stored, fallback to the field langcode.
      // Documented in initializeLangcode().
      $langcode = $this->getLangcode();
    }

    return $langcode;
  }

  /**
   * {@inheritdoc}
   */
  public function getCountryCode() {
    return $this->country_code;
  }

  /**
   * {@inheritdoc}
   */
  public function getAdministrativeArea() {
    return $this->administrative_area;
  }

  /**
   * {@inheritdoc}
   */
  public function getLocality() {
    return $this->locality;
  }

  /**
   * {@inheritdoc}
   */
  public function getDependentLocality() {
    return $this->dependent_locality;
  }

  /**
   * {@inheritdoc}
   */
  public function getPostalCode() {
    return $this->postal_code;
  }

  /**
   * {@inheritdoc}
   */
  public function getSortingCode() {
    return $this->sorting_code;
  }

  /**
   * {@inheritdoc}
   */
  public function getAddressLine1() {
    return $this->address_line1;
  }

  /**
   * {@inheritdoc}
   */
  public function getAddressLine2() {
    return $this->address_line2;
  }

  /**
   * {@inheritdoc}
   */
  public function getOrganization() {
    return $this->organization;
  }

  /**
   * {@inheritdoc}
   */
  public function getGivenName() {
    return $this->given_name;
  }

  /**
   * {@inheritdoc}
   */
  public function getAdditionalName() {
    return $this->additional_name;
  }

  /**
   * {@inheritdoc}
   */
  public function getFamilyName() {
    return $this->family_name;
  }

}
