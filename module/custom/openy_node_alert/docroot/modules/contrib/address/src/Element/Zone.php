<?php

namespace Drupal\address\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\FormElement;
use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\NestedArray;

/**
 * Provides a zone form element.
 *
 * Use it to populate a \CommerceGuys\Addressing\Zone\Zone object.
 *
 * Note that the default value does not need to contain a 'label'
 * property if #show_label_field is FALSE.
 *
 * Usage example:
 * @code
 * $form['zone'] = [
 *   '#type' => 'address_zone',
 *   '#default_value' => [
 *     'label' => t('California and Nevada'),
 *     'territories' => [
 *       ['country_code' => 'US', 'administrative_area' => 'CA'],
 *       ['country_code' => 'US', 'administrative_area' => 'NV'],
 *     ],
 *   ],
 *   '#show_label_field' => TRUE,
 *   '#available_countries' => ['US', 'FR'],
 * ];
 * @endcode
 *
 * @FormElement("address_zone")
 */
class Zone extends FormElement {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_called_class();
    return [
      '#show_label_field' => FALSE,
      // List of country codes. If empty, all countries will be available.
      '#available_countries' => [],

      '#input' => TRUE,
      '#multiple' => FALSE,
      '#default_value' => NULL,
      '#process' => [
        [$class, 'processZone'],
        [$class, 'processGroup'],
      ],
      '#element_validate' => [
        [$class, 'validateZone'],
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
    foreach (['label', 'territories'] as $property) {
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
    if (is_array($input)) {
      $input = self::applyDefaults($input);
    }
    $element['#default_value'] = (array) $element['#default_value'];
    $element['#default_value'] = self::applyDefaults($element['#default_value']);

    return is_array($input) ? $input : $element['#default_value'];
  }

  /**
   * Processes the zone form element.
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
   *   Thrown when the #default_value is malformed.
   */
  public static function processZone(array &$element, FormStateInterface $form_state, array &$complete_form) {
    if (!empty($element['#default_value']['territories']) && !is_array($element['#default_value']['territories'])) {
      throw new \InvalidArgumentException('The #default_value "territories" property must be an array.');
    }
    $id_prefix = implode('-', $element['#parents']);
    $wrapper_id = Html::getUniqueId($id_prefix . '-ajax-wrapper');
    $button_id_prefix = implode('_', $element['#parents']);
    $value = $element['#value'];
    $element_state = self::getElementState($element['#parents'], $form_state);
    if (!isset($element_state['territories'])) {
      // Default to a single empty row if no other value was provided.
      $element_state['territories'] = $value['territories'];
      $element_state['territories'] = $element_state['territories'] ?: [NULL];
      self::setElementState($element['#parents'], $form_state, $element_state);
    }

    $element = [
      '#tree' => TRUE,
      '#prefix' => '<div id="' . $wrapper_id . '">',
      '#suffix' => '</div>',
    ] + $element;
    $element['label'] = [
      '#type' => 'textfield',
      '#title' => t('Zone label'),
      '#default_value' => $value['label'],
      '#access' => $element['#show_label_field'],
    ];
    $element['territories'] = [
      '#type' => 'table',
      '#header' => [
        t('Territory'),
        t('Operations'),
      ],
      '#input' => FALSE,
    ];
    foreach ($element_state['territories'] as $index => $territory) {
      $territory_form = &$element['territories'][$index];
      $territory_form['territory'] = [
        '#type' => 'address_zone_territory',
        '#default_value' => $territory,
        '#available_countries' => $element['#available_countries'],
        '#required' => $element['#required'],
        // Remove the 'territory' level from form state values.
        '#parents' => array_merge($element['#parents'], ['territories', $index]),
      ];
      $territory_form['remove'] = [
        '#type' => 'submit',
        '#name' => $button_id_prefix . '_remove_territory' . $index,
        '#value' => t('Remove'),
        '#limit_validation_errors' => [],
        '#submit' => [[get_called_class(), 'removeTerritorySubmit']],
        '#territory_index' => $index,
        '#ajax' => [
          'callback' => [get_called_class(), 'ajaxRefresh'],
          'wrapper' => $wrapper_id,
        ],
      ];
    }
    $element['territories'][] = [
      'add_territory' => [
        '#type' => 'submit',
        '#name' => $button_id_prefix . '_add_territory',
        '#value' => t('Add territory'),
        '#submit' => [[get_called_class(), 'addTerritorySubmit']],
        '#limit_validation_errors' => [],
        '#ajax' => [
          'callback' => [get_called_class(), 'ajaxRefresh'],
          'wrapper' => $wrapper_id,
        ],
      ],
    ];

    return $element;
  }

  /**
   * Validates the zone.
   *
   * @param array $element
   *   The form element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public static function validateZone(array $element, FormStateInterface $form_state) {
    $value = $form_state->getValue($element['#parents']);
    // Remove empty territories, unneeded keys.
    foreach ($value['territories'] as $index => $territory) {
      if (empty($territory['country_code'])) {
        unset($value['territories'][$index]);
      }
      unset($territory['remove']);
      unset($territory['add_territory']);
    }
    $value['territories'] = array_filter($value['territories']);
    $form_state->setValue($element['#parents'], $value);
    // Required zones must always have a territory.
    // @todo Invent a nicer UX for optional zones.
    if ($element['#required'] && empty($value['territories'])) {
      $form_state->setError($element['territories'], t('Please add at least one territory.'));
    }
  }

  /**
   * Ajax callback.
   */
  public static function ajaxRefresh(array $form, FormStateInterface $form_state) {
    $triggering_element = $form_state->getTriggeringElement();
    return NestedArray::getValue($form, array_slice($triggering_element['#array_parents'], 0, -3));
  }

  /**
   * Submit callback for adding a new territory.
   */
  public static function addTerritorySubmit(array $form, FormStateInterface $form_state) {
    $triggering_element = $form_state->getTriggeringElement();
    $element_parents = array_slice($triggering_element['#parents'], 0, -3);
    $element_state = self::getElementState($element_parents, $form_state);
    $element_state['territories'][] = NULL;
    self::setElementState($element_parents, $form_state, $element_state);
    $form_state->setRebuild();
  }

  /**
   * Submit callback for removing a territory.
   */
  public static function removeTerritorySubmit(array $form, FormStateInterface $form_state) {
    $triggering_element = $form_state->getTriggeringElement();
    $element_parents = array_slice($triggering_element['#parents'], 0, -3);
    $element_state = self::getElementState($element_parents, $form_state);
    $territory_index = $triggering_element['#territory_index'];
    unset($element_state['territories'][$territory_index]);
    self::setElementState($element_parents, $form_state, $element_state);
    $form_state->setRebuild();
  }

  /**
   * Gets the element state.
   *
   * @param array $parents
   *   The element parents.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   The element state.
   */
  public static function getElementState(array $parents, FormStateInterface $form_state) {
    $parents = array_merge(['element_state', '#parents'], $parents);
    return NestedArray::getValue($form_state->getStorage(), $parents);
  }

  /**
   * Sets the element state.
   *
   * @param array $parents
   *   The element parents.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param array $element_state
   *   The element state.
   */
  public static function setElementState(array $parents, FormStateInterface $form_state, array $element_state) {
    $parents = array_merge(['element_state', '#parents'], $parents);
    NestedArray::setValue($form_state->getStorage(), $parents, $element_state);
  }

}
