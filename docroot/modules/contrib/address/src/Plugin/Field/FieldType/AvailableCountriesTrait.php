<?php

namespace Drupal\address\Plugin\Field\FieldType;

use Drupal\address\Event\AddressEvents;
use Drupal\address\Event\AvailableCountriesEvent;
use Drupal\Core\Form\FormStateInterface;

/**
 * Allows field types to limit the available countries.
 */
trait AvailableCountriesTrait {

  /**
   * An altered list of available countries.
   *
   * @var array
   */
  protected static $availableCountries = [];

  /**
   * Defines the default field-level settings.
   *
   * @return array
   *   A list of default settings, keyed by the setting name.
   */
  public static function defaultCountrySettings() {
    return [
      'available_countries' => [],
    ];
  }

  /**
   * Builds the field settings form.
   *
   * @param array $form
   *   The form where the settings form is being included in.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state of the (entire) configuration form.
   *
   * @return array
   *   The modified form.
   */
  public function countrySettingsForm(array $form, FormStateInterface $form_state) {
    $element = [];
    $element['available_countries'] = [
      '#type' => 'select',
      '#title' => $this->t('Available countries'),
      '#description' => $this->t('If no countries are selected, all countries will be available.'),
      '#options' => \Drupal::service('address.country_repository')->getList(),
      '#default_value' => $this->getSetting('available_countries'),
      '#multiple' => TRUE,
      '#size' => 10,
    ];

    return $element;
  }

  /**
   * Gets the available countries for the current field.
   *
   * @return array
   *   A list of country codes.
   */
  public function getAvailableCountries() {
    // Alter the list once per field, instead of once per field delta.
    $field_definition = $this->getFieldDefinition();
    $definition_id = spl_object_hash($field_definition);
    if (!isset(static::$availableCountries[$definition_id])) {
      $available_countries = array_filter($this->getSetting('available_countries'));
      $event_dispatcher = \Drupal::service('event_dispatcher');
      $event = new AvailableCountriesEvent($available_countries, $field_definition);
      $event_dispatcher->dispatch(AddressEvents::AVAILABLE_COUNTRIES, $event);
      static::$availableCountries[$definition_id] = $event->getAvailableCountries();
    }

    return static::$availableCountries[$definition_id];
  }

}
