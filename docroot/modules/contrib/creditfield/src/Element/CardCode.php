<?php

/**
 * @file
 * Contains \Drupal\creditfield\Element\CardCode.
 */

namespace Drupal\creditfield\Element;

use \Drupal\Core\Render\Element\FormElement;
use \Drupal\Core\Render\Element;
use \Drupal\Core\Form\FormStateInterface;
use \Drupal\Component\Utility\Unicode as Unicode;

/**
 * Provides a one-line credit card number field form element.
 *
 * @FormElement("creditfield_cardcode")
 */
class CardCode extends FormElement {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);

    return array(
      '#input' => TRUE,
      '#maxlength' => 4,
      '#autocomplete_route_name' => FALSE,
      '#element_validate' => array(
        array($class, 'validateCardCode')
      ),
      '#process' => array(
        array($class, 'processCardCode'),
      ),
      '#pre_render' => array(
        array($class, 'preRenderCardCode'),
      ),
      '#theme' => 'input__textfield',
      '#theme_wrappers' => array('form_element'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function processCardCode(&$element, FormStateInterface $form_state, &$complete_form) {
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public static function validateCardCode(&$element, FormStateInterface $form_state, &$complete_form) {
    if (!static::numberIsValid($element['#value'])) {
      $form_state->setError($element, t('Please enter a valid card code.'));
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
   * Prepares a #type 'creditfield_code' render element for input.html.twig.
   *
   * @param array $element
   *   An associative array containing the properties of the element.
   *   Properties used: #title, #value, #description, #size, #maxlength,
   *   #placeholder, #required, #attributes.
   *
   * @return array
   *   The $element with prepared variables ready for input.html.twig.
   */
  public static function preRenderCardCode($element) {
    $element['#attributes']['type'] = 'text';
    Element::setAttributes($element, array('id', 'name', 'value', 'size', 'maxlength', 'placeholder'));
    static::setAttributes($element, array('form-text'));
    return $element;
  }

  /**
   * Validation of the value submitted in the creditfield_cardcode field.
   * @param $value
   * @return bool
   */
  public static function numberIsValid($value) {
    // value is not an integer or is an integer but not between 3 and 4 digits
    return (bool) preg_match('/^\d{3,4}$/', $value);
  }
}