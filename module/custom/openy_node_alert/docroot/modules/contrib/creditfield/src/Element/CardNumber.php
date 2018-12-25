<?php

/**
 * @file
 * Contains \Drupal\creditfield\Element\CardNumber.
 */

namespace Drupal\creditfield\Element;

use \Drupal\Core\Render\Element\FormElement;
use \Drupal\Core\Render\Element;
use \Drupal\Core\Form\FormStateInterface;
use \Drupal\Component\Utility\Unicode as Unicode;

/**
 * Provides a one-line credit card number field form element.
 *
 * @FormElement("creditfield_cardnumber")
 */
class CardNumber extends FormElement {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);

    return array(
      '#input' => TRUE,
      '#size' => 60,
      '#maxlength' => 16,
      '#autocomplete_route_name' => FALSE,
      '#element_validate' => array(
        array($class, 'validateCardNumber')
      ),
      '#process' => array(
        array($class, 'processCardNumber'),
      ),
      '#pre_render' => array(
        array($class, 'preRenderCardNumber'),
      ),
      '#theme' => 'input__textfield',
      '#theme_wrappers' => array('form_element'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function processCardNumber(&$element, FormStateInterface $form_state, &$complete_form) {
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public static function validateCardNumber(&$element, FormStateInterface $form_state, &$complete_form) {
    if (!static::numberIsValid($element['#value'])) {
      $form_state->setError($element, t('Your card appears to be invalid. Please check the numbers and try again.'));
      return;
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function valueCallback(&$element, $input, FormStateInterface $form_state) {
    if ($input !== FALSE && $input !== NULL) {
      // Equate $input to the form value to ensure it's marked for
      // validation.
      return str_replace(array("\r", "\n"), '', $input);
    }
  }

  /**
   * Prepares a #type 'creditfield_cardnumber' render element for input.html.twig.
   *
   * @param array $element
   *   An associative array containing the properties of the element.
   *   Properties used: #title, #value, #description, #size, #maxlength,
   *   #placeholder, #required, #attributes.
   *
   * @return array
   *   The $element with prepared variables ready for input.html.twig.
   */
  public static function preRenderCardNumber($element) {
    $element['#attributes']['type'] = 'text';
    Element::setAttributes($element, array('id', 'name', 'value', 'size', 'maxlength', 'placeholder'));
    static::setAttributes($element, array('form-text'));
    return $element;
  }

  /**
   * Validate callback for credit card number form fields.
   * Luhn algorithm number checker - (c) 2005-2008 shaman - www.planzero.org
   * @param $value
   * @return bool
   */
  public static function numberIsValid($value) {
    // short circuit here if value is not an integer
    if (!preg_match('/^\d+$/', $value)) {
      return FALSE;
    }

    // Set the string length and parity
    $cardnumber_length = Unicode::strlen($value);

    if ($cardnumber_length < 14 || $cardnumber_length > 16) {
      return FALSE;
    }

    $parity = $cardnumber_length % 2;

    // Loop through each digit and do the maths
    $total=0;

    for ($i = 0; $i < $cardnumber_length; $i++) {
      $digit = $value[$i];
      // Multiply alternate digits by two
      if ($i % 2 == $parity) {
        $digit *= 2;
        // If the sum is two digits, add them together (in effect)
        if ($digit > 9) {
          $digit -= 9;
        }
      }
      // Total up the digits
      $total += $digit;
    }

    // If the total mod 10 equals 0, the number is valid
    return ($total % 10 == 0) ? TRUE : FALSE;
  }
}