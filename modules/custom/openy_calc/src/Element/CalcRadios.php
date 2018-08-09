<?php

namespace Drupal\openy_calc\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\Radios;

/**
 * Provides a form element for a set of radio buttons.
 *
 * @FormElement("calc_radios")
 */
class CalcRadios extends Radios {

  /**
   * {@inheritdoc}
   */
  public static function processRadios(&$element, FormStateInterface $form_state, &$complete_form) {
    $element = parent::processRadios($element, $form_state, $complete_form);
    if (count($element['#options']) > 0) {
      foreach ($element['#options'] as $key => $choice) {
        $element[$key]['#element_variables'] = $element['#element_variables'];
        $element[$key]['#type'] = $element['#subtype'];
      }
    }
    return $element;
  }

}
