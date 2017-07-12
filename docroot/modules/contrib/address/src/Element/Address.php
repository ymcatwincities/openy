<?php

namespace Drupal\address\Element;

use CommerceGuys\Addressing\AddressFormat\AddressField;
use CommerceGuys\Addressing\AddressFormat\AddressFormat;
use CommerceGuys\Addressing\AddressFormat\AddressFormatHelper;
use CommerceGuys\Addressing\LocaleHelper;
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
      // List of AddressField constants. If empty, all fields will be used.
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
   * {@inheritdoc}
   */
  public static function valueCallback(&$element, $input, FormStateInterface $form_state) {
    if (is_array($input)) {
      $value = $input;
    }
    else {
      if (!is_array($element['#default_value'])) {
        $element['#default_value'] = [];
      }
      $value = $element['#default_value'];
    }
    // Initialize default keys.
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
    if (isset($element['#used_fields']) && !is_array($element['#used_fields'])) {
      throw new \InvalidArgumentException('The #used_fields property must be an array.');
    }
    $id_prefix = implode('-', $element['#parents']);
    $wrapper_id = Html::getUniqueId($id_prefix . '-ajax-wrapper');
    $value = $element['#value'];
    if (empty($value['country_code']) && $element['#required']) {
      // Preselect the default country so that the other elements can be shown.
      $value['country_code'] = Country::getDefaultCountry($element['#available_countries']);
    }

    $element = [
      '#tree' => TRUE,
      '#prefix' => '<div id="' . $wrapper_id . '">',
      '#suffix' => '</div>',
      // Pass the id along to other methods.
      '#wrapper_id' => $wrapper_id,
    ] + $element;
    $element['langcode'] = [
      '#type' => 'hidden',
      '#value' => $value['langcode'],
    ];
    $element['country_code'] = [
      '#type' => 'address_country',
      '#title' => t('Country'),
      '#available_countries' => $element['#available_countries'],
      '#default_value' => $value['country_code'],
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
    /** @var \CommerceGuys\Addressing\AddressFormat\AddressFormat $address_format */
    $address_format = \Drupal::service('address.address_format_repository')->get($value['country_code']);
    $required_fields = $address_format->getRequiredFields();
    $labels = LabelHelper::getFieldLabels($address_format);
    $locale = \Drupal::languageManager()->getConfigOverrideLanguage()->getId();
    if (LocaleHelper::match($address_format->getLocale(), $locale)) {
      $format_string = $address_format->getLocalFormat();
    }
    else {
      $format_string = $address_format->getFormat();
    }
    $grouped_fields = AddressFormatHelper::getGroupedFields($format_string);
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
    // Hide unused fields.
    if (!empty($element['#used_fields'])) {
      $used_fields = $element['#used_fields'];
      $unused_fields = array_diff(AddressField::getAll(), $used_fields);
      foreach ($unused_fields as $field) {
        $property = FieldHelper::getPropertyName($field);
        $element[$property]['#access'] = FALSE;
      }
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
    $country_element = $form_state->getTriggeringElement();
    $address_element = NestedArray::getValue($form, array_slice($country_element['#array_parents'], 0, -2));

    return $address_element;
  }

  /**
   * Clears the country-specific form values when the country changes.
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

    $triggering_element_name = end($triggering_element['#parents']);
    if ($triggering_element_name == 'country_code') {
      $keys = [
        'dependent_locality', 'locality', 'administrative_area',
        'postal_code', 'sorting_code',
      ];
      $input = &$form_state->getUserInput();
      foreach ($keys as $key) {
        $parents = array_merge($element['#parents'], [$key]);
        NestedArray::setValue($input, $parents, '');
        $element[$key]['#value'] = '';
      }
    }

    return $element;
  }

}
