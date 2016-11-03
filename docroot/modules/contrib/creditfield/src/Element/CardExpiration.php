<?php

/**
 * @file
 * Contains \Drupal\creditfield\Render\CardExpiration.
 */

namespace Drupal\creditfield\Element;

use \Drupal\Core\Form\FormStateInterface;
use \Drupal\Core\Render\Element\FormElement;
use \Drupal\Core\Render\Element;
use \Drupal\Component\Utility\Unicode as Unicode;

/**
 * Provides a one-line credit card number field form element.
 *
 * @FormElement("creditfield_expiration")
 */
class CardExpiration extends FormElement {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);

    return array(
      '#input' => TRUE,
      '#element_validate' => array(
        array($class, 'validateCardExpiration')
      ),
      '#process' => array(
        array($class, 'processCardExpiration'),
      ),
      '#pre_render' => array(
        array($class, 'preRenderCardExpiration'),
      ),
      '#theme' => 'input__date',
      '#theme_wrappers' => array('form_element'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function processCardExpiration(&$element, FormStateInterface $form_state, &$complete_form) {
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public static function validateCardExpiration(&$element, FormStateInterface $form_state, &$complete_form) {
    if (!static::dateIsValid($element['#value'])) {
      $form_state->setError($element, t('Please enter a valid expiration date.'));
    }
  }

  /**
   * Adds form-specific attributes to a 'creditfield_expiration' #type element.
   *
   * Supports HTML5 types of 'date', 'datetime', 'datetime-local', and 'time'.
   * Falls back to a plain textfield. Used as a sub-element by the datetime
   * element type.
   *
   * @param array $element
   *   An associative array containing the properties of the element.
   *   Properties used: #title, #value, #options, #description, #required,
   *   #attributes, #id, #name, #type, #min, #max, #step, #value, #size.
   *
   * Note: The input "name" attribute needs to be sanitized before output, which
   *       is currently done by initializing Drupal\Core\Template\Attribute with
   *       all the attributes.
   *
   * @return array
   *   The $element with prepared variables ready for #theme 'input__date'.
   */
  public static function preRenderCardExpiration($element) {
    $element['#attributes']['type'] = 'month';
    Element::setAttributes($element, array('id', 'name', 'type', 'min', 'max', 'step', 'value', 'size'));
    static::setAttributes($element, array('form-' . $element['#attributes']['type']));

    return $element;
  }

  /**
   * Simple date check to determine if the expiration date is in the future from right now.
   * @param $value
   * @return bool
   */
  public static function dateIsValid($value) {
    if (!Unicode::strlen($value)) {
      return FALSE;
    }

    $dateparts = explode('-', $value);
    $year = (int) $dateparts[0];
    $month = (int) $dateparts[1];

    if ($year < date('Y') || !is_integer($year)) {
      return FALSE;
    }

    if ($year == date('Y') && $month <= date('m')) {
      return FALSE;
    }

    return TRUE;
  }
}