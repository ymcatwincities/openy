<?php

namespace Drupal\address\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\FormElement;

/**
 * Provides a country form element.
 *
 * Usage example:
 * @code
 * $form['country'] = [
 *   '#type' => 'address_country',
 *   '#default_value' => 'DE',
 *   '#available_countries' => ['DE', 'FR'],
 * ];
 * @endcode
 *
 * @FormElement("address_country")
 */
class Country extends FormElement {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    return [
      // List of country codes. If empty, all countries will be available.
      '#available_countries' => [],

      '#input' => TRUE,
      '#multiple' => FALSE,
      '#default_value' => NULL,
      '#process' => [
        [$class, 'processCountry'],
        [$class, 'processGroup'],
      ],
      '#theme_wrappers' => ['container'],
    ];
  }

  /**
   * Processes the address_country form element.
   *
   * @param array $element
   *   The form element to process.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param array $complete_form
   *   The complete form structure.
   *
   * @return array
   *   The processed element.
   *
   * @throws \InvalidArgumentException
   *   Thrown when #available_countries is malformed.
   */
  public static function processCountry(array &$element, FormStateInterface $form_state, array &$complete_form) {
    if (isset($element['#available_countries']) && !is_array($element['#available_countries'])) {
      throw new \InvalidArgumentException('The #available_countries property must be an array.');
    }

    $full_country_list = \Drupal::service('address.country_repository')->getList();
    $country_list = $full_country_list;
    if (!empty($element['#available_countries'])) {
      $available_countries = $element['#available_countries'];
      if (!empty($element['#default_value'])) {
        // The current country should always be available.
        $available_countries[] = $element['#default_value'];
      }
      $available_countries = array_combine($available_countries, $available_countries);
      $country_list = array_intersect_key($country_list, $available_countries);
    }
    if (empty($element['#default_value']) && $element['#required']) {
      // Fallback to the first country in the list if the default country
      // is empty even though the field is required.
      $element['#default_value'] = key($country_list);
    }

    $element['#tree'] = TRUE;
    // Hide the dropdown when there is only one possible value.
    if (count($country_list) == 1 && $element['#required']) {
      $element['country_code'] = [
        '#type' => 'hidden',
        '#value' => key($available_countries),
      ];
    }
    else {
      $element['country_code'] = [
        '#type' => 'select',
        '#title' => $element['#title'],
        '#options' => $country_list,
        '#default_value' => $element['#default_value'],
        '#required' => $element['#required'],
        '#limit_validation_errors' => [],
        '#attributes' => [
          'class' => ['country'],
          'autocomplete' => 'country',
        ],
        '#weight' => -100,
      ];
      if (!$element['#required']) {
        $element['country_code']['#empty_value'] = '';
      }
      if (!empty($element['#ajax'])) {
        $element['country_code']['#ajax'] = $element['#ajax'];
        unset($element['#ajax']);
      }
    }
    // Remove the 'country_code' level from form state values.
    $element['country_code']['#parents'] = $element['#parents'];

    return $element;
  }

  /**
   * Gets the default country based on the available countries.
   *
   * Used as a helper by parent form elements (Address, ZoneTerritory).
   *
   * @param array $available_countries
   *   The available countries, an array of country codes.
   *
   * @return string
   *   The default country.
   */
  public static function getDefaultCountry(array $available_countries = []) {
    $full_country_list = \Drupal::service('address.country_repository')->getList();
    $country_list = $full_country_list;
    if (!empty($available_countries)) {
      $available_countries = array_combine($available_countries, $available_countries);
      $country_list = array_intersect_key($country_list, $available_countries);
    }
    $default_country = key($country_list);

    return $default_country;
  }

}
