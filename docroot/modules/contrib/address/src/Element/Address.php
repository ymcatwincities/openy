<?php

namespace Drupal\address\Element;

use CommerceGuys\Addressing\AddressFormat\AddressField;
use CommerceGuys\Addressing\AddressFormat\AddressFormat;
use CommerceGuys\Addressing\AddressFormat\AddressFormatHelper;
use CommerceGuys\Addressing\AddressFormat\FieldOverride;
use CommerceGuys\Addressing\AddressFormat\FieldOverrides;
use CommerceGuys\Addressing\Locale;
use Drupal\address\FieldHelper;
use Drupal\address\LabelHelper;
use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\NestedArray;
use Drupal\Component\Utility\SortArray;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Render\Element\FormElement;

/**
 * Provides an address form element.
 *
 * Use #field_overrides to override the country-specific address format,
 * forcing specific fields to be hidden, optional, or required.
 *
 * Usage example:
 * @code
 * $form['address'] = [
 *   '#type' => 'address',
 *   '#default_value' => [
 *     'given_name' => 'John',
 *     'family_name' => 'Smith',
 *     'organization' => 'Google Inc.',
 *     'address_line1' => '1098 Alta Ave',
 *     'postal_code' => '94043',
 *     'locality' => 'Mountain View',
 *     'administrative_area' => 'CA',
 *     'country_code' => 'US',
 *     'langcode' => 'en',
 *   ],
 *   '@field_overrides' => [
 *     AddressField::ORGANIZATION => FieldOverride::REQUIRED,
 *     AddressField::ADDRESS_LINE2 => FieldOverride::HIDDEN,
 *     AddressField::POSTAL_CODE => FieldOverride::OPTIONAL,
 *   ],
 *   '#available_countries' => ['DE', 'FR'],
 * ];
 * @endcode
 *
 * @FormElement("address")
 */
class Address extends FormElement {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    return [
      // List of country codes. If empty, all countries will be available.
      '#available_countries' => [],
      // FieldOverride constants keyed by AddressField constants.
      '#field_overrides' => [],
      // Deprecated. Use #field_overrides instead.
      '#used_fields' => [],

      '#input' => TRUE,
      '#multiple' => FALSE,
      '#default_value' => NULL,
      '#process' => [
        [$class, 'processAddress'],
        [$class, 'processGroup'],
      ],
      '#pre_render' => [
        [$class, 'groupElements'],
        [$class, 'preRenderGroup'],
      ],
      '#after_build' => [
        [$class, 'clearValues'],
      ],
      '#attached' => [
        'library' => ['address/form'],
      ],
      '#theme_wrappers' => ['container'],
    ];
  }

  /**
   * Ensures all keys are set on the provided value.
   *
   * @param array $value
   *   The value.
   *
   * @return array
   *   The modified value.
   */
  public static function applyDefaults(array $value) {
    $properties = [
      'given_name', 'additional_name', 'family_name', 'organization',
      'address_line1', 'address_line2', 'postal_code', 'sorting_code',
      'dependent_locality', 'locality', 'administrative_area',
      'country_code', 'langcode',
    ];
    foreach ($properties as $property) {
      if (!isset($value[$property])) {
        $value[$property] = NULL;
      }
    }

    return $value;
  }

  /**
   * {@inheritdoc}
   */
  public static function valueCallback(&$element, $input, FormStateInterface $form_state) {
    // Ensure both the default value and the input have all keys set.
    // Preselect the default country to ensure it's present in the value.
    $element['#default_value'] = (array) $element['#default_value'];
    $element['#default_value'] = self::applyDefaults($element['#default_value']);
    if (empty($element['#default_value']['country_code']) && $element['#required']) {
      $element['#default_value']['country_code'] = Country::getDefaultCountry($element['#available_countries']);
    }
    if (is_array($input)) {
      $input = self::applyDefaults($input);
      if (empty($input['country_code']) && $element['#required']) {
        $input['country_code'] = $element['#default_value']['country_code'];
      }
    }

    return is_array($input) ? $input : $element['#default_value'];
  }

  /**
   * Processes the address form element.
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
   *   Thrown when #used_fields is malformed.
   */
  public static function processAddress(array &$element, FormStateInterface $form_state, array &$complete_form) {
    // Convert #used_fields into #field_overrides.
    if (!empty($element['#used_fields']) && is_array($element['#used_fields'])) {
      $unused_fields = array_diff(AddressField::getAll(), $element['#used_fields']);
      $element['#field_overrides'] = [];
      foreach ($unused_fields as $field) {
        $element['#field_overrides'][$field] = FieldOverride::HIDDEN;
      }
      unset($element['#used_fields']);
    }
    // Validate and parse #field_overrides.
    if (!is_array($element['#field_overrides'])) {
      throw new \InvalidArgumentException('The #field_overrides property must be an array.');
    }
    $element['#parsed_field_overrides'] = new FieldOverrides($element['#field_overrides']);

    $id_prefix = implode('-', $element['#parents']);
    $wrapper_id = Html::getUniqueId($id_prefix . '-ajax-wrapper');
    // The #value has the new values on #ajax, the #default_value otherwise.
    $value = $element['#value'];

    $element = [
      '#tree' => TRUE,
      '#prefix' => '<div id="' . $wrapper_id . '">',
      '#suffix' => '</div>',
      // Pass the id along to other methods.
      '#wrapper_id' => $wrapper_id,
    ] + $element;
    $element['langcode'] = [
      '#type' => 'hidden',
      '#value' => $element['#default_value']['langcode'],
    ];
    $element['country_code'] = [
      '#type' => 'address_country',
      '#title' => t('Country'),
      '#available_countries' => $element['#available_countries'],
      '#default_value' => $element['#default_value']['country_code'],
      '#required' => $element['#required'],
      '#limit_validation_errors' => [],
      '#ajax' => [
        'callback' => [get_called_class(), 'ajaxRefresh'],
        'wrapper' => $wrapper_id,
      ],
      '#weight' => -100,
    ];
    if (!empty($value['country_code'])) {
      $element = static::addressElements($element, $value);
    }

    return $element;
  }

  /**
   * Builds the format-specific address elements.
   *
   * @param array $element
   *   The existing form element array.
   * @param array $value
   *   The address value, in $property_name => $value format.
   *
   * @return array
   *   The modified form element array containing the format specific elements.
   */
  protected static function addressElements(array $element, array $value) {
    $size_attributes = [
      AddressField::ADMINISTRATIVE_AREA => 30,
      AddressField::LOCALITY => 30,
      AddressField::DEPENDENT_LOCALITY => 30,
      AddressField::POSTAL_CODE => 10,
      AddressField::SORTING_CODE => 10,
      AddressField::GIVEN_NAME => 25,
      AddressField::ADDITIONAL_NAME => 25,
      AddressField::FAMILY_NAME => 25,
    ];
    $field_overrides = $element['#parsed_field_overrides'];
    /** @var \CommerceGuys\Addressing\AddressFormat\AddressFormat $address_format */
    $address_format = \Drupal::service('address.address_format_repository')->get($value['country_code']);
    $required_fields = AddressFormatHelper::getRequiredFields($address_format, $field_overrides);
    $labels = LabelHelper::getFieldLabels($address_format);
    $locale = \Drupal::languageManager()->getConfigOverrideLanguage()->getId();
    if (Locale::matchCandidates($address_format->getLocale(), $locale)) {
      $format_string = $address_format->getLocalFormat();
    }
    else {
      $format_string = $address_format->getFormat();
    }
    $grouped_fields = AddressFormatHelper::getGroupedFields($format_string, $field_overrides);
    foreach ($grouped_fields as $line_index => $line_fields) {
      if (count($line_fields) > 1) {
        // Used by the #pre_render callback to group fields inline.
        $element['container' . $line_index] = [
          '#type' => 'container',
          '#attributes' => [
            'class' => ['address-container-inline'],
          ],
        ];
      }

      foreach ($line_fields as $field_index => $field) {
        $property = FieldHelper::getPropertyName($field);
        $class = str_replace('_', '-', $property);

        $element[$property] = [
          '#type' => 'textfield',
          '#title' => $labels[$field],
          '#default_value' => isset($value[$property]) ? $value[$property] : '',
          '#required' => in_array($field, $required_fields),
          '#size' => isset($size_attributes[$field]) ? $size_attributes[$field] : 60,
          '#attributes' => [
            'class' => [$class],
            'autocomplete' => FieldHelper::getAutocompleteAttribute($field),
          ],
        ];
        if (count($line_fields) > 1) {
          $element[$property]['#group'] = $line_index;
        }
      }
    }
    // Hide the label for the second address line.
    if (isset($element['address_line2'])) {
      $element['address_line2']['#title_display'] = 'invisible';
    }
    // Add predefined options to the created subdivision elements.
    $element = static::processSubdivisionElements($element, $value, $address_format);

    return $element;
  }

  /**
   * Processes the subdivision elements, adding predefined values where found.
   *
   * @param array $element
   *   The existing form element array.
   * @param array $value
   *   The address value, in $property_name => $value format.
   * @param \CommerceGuys\Addressing\AddressFormat\AddressFormat $address_format
   *   The address format.
   *
   * @return array
   *   The processed form element array.
   */
  protected static function processSubdivisionElements(array $element, array $value, AddressFormat $address_format) {
    $depth = $address_format->getSubdivisionDepth();
    if ($depth === 0) {
      // No predefined data found.
      return $element;
    }

    $subdivision_properties = [];
    foreach ($address_format->getUsedSubdivisionFields() as $field) {
      $subdivision_properties[] = FieldHelper::getPropertyName($field);
    }
    // Load and insert the subdivisions for each parent id.
    $locale = \Drupal::languageManager()->getConfigOverrideLanguage()->getId();
    $current_depth = 1;
    $parents = [];
    foreach ($subdivision_properties as $index => $property) {
      if (!isset($element[$property]) || !Element::isVisibleElement($element[$property])) {
        break;
      }
      $parent_property = $index ? $subdivision_properties[$index - 1] : 'country_code';
      if ($parent_property && empty($value[$parent_property])) {
        break;
      }
      $parents[] = $value[$parent_property];
      $subdivisions = \Drupal::service('address.subdivision_repository')->getList($parents, $locale);
      if (empty($subdivisions)) {
        break;
      }

      $element[$property]['#type'] = 'select';
      $element[$property]['#options'] = $subdivisions;
      $element[$property]['#empty_value'] = '';
      unset($element[$property]['#size']);
      if ($current_depth < $depth) {
        $element[$property]['#ajax'] = [
          'callback' => [get_called_class(), 'ajaxRefresh'],
          'wrapper' => $element['#wrapper_id'],
        ];
      }

      $current_depth++;
    }

    return $element;
  }

  /**
   * Groups elements with the same #group so that they can be inlined.
   */
  public static function groupElements(array $element) {
    $sort = [];
    foreach (Element::getVisibleChildren($element) as $key) {
      if (isset($element[$key]['#group'])) {
        // Copy the element to the container and remove the original.
        $group_index = $element[$key]['#group'];
        $container_key = 'container' . $group_index;
        $element[$container_key][$key] = $element[$key];
        unset($element[$key]);
        // Mark the container for sorting.
        if (!in_array($container_key, $sort)) {
          $sort[] = $container_key;
        }
      }
    }
    // Sort the moved elements, so that their #weight stays respected.
    foreach ($sort as $key) {
      uasort($element[$key], [SortArray::class, 'sortByWeightProperty']);
    }

    return $element;
  }

  /**
   * Ajax callback.
   */
  public static function ajaxRefresh(array $form, FormStateInterface $form_state) {
    $triggering_element = $form_state->getTriggeringElement();
    $parents = $triggering_element['#array_parents'];
    $triggering_element_name = array_pop($parents);
    // The country_code element is nested one level deeper than
    // the subdivision elements.
    if ($triggering_element_name == 'country_code') {
      array_pop($parents);
    };
    $address_element = NestedArray::getValue($form, $parents);

    return $address_element;
  }

  /**
   * Clears dependent form values when the country or subdivision changes.
   *
   * Implemented as an #after_build callback because #after_build runs before
   * validation, allowing the values to be cleared early enough to prevent the
   * "Illegal choice" error.
   */
  public static function clearValues(array $element, FormStateInterface $form_state) {
    $triggering_element = $form_state->getTriggeringElement();
    if (!$triggering_element) {
      return $element;
    }

    $keys = [
      'country_code' => [
        'dependent_locality', 'locality', 'administrative_area',
        'postal_code', 'sorting_code',
      ],
      'administrative_area' => [
        'dependent_locality', 'locality',
      ],
      'locality' => [
        'dependent_locality',
      ],
    ];
    $triggering_element_name = end($triggering_element['#parents']);
    if (isset($keys[$triggering_element_name])) {
      $input = &$form_state->getUserInput();
      foreach ($keys[$triggering_element_name] as $key) {
        $parents = array_merge($element['#parents'], [$key]);
        NestedArray::setValue($input, $parents, '');
        $element[$key]['#value'] = '';
      }
    }

    return $element;
  }

}
