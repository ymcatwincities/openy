<?php

namespace Drupal\openy_calc\Element;

use Drupal\Core\Render\Element\Radio;

/**
 * Provides a form element for a single radio button (membership type).
 *
 * @FormElement("type_radio")
 */
class TypeRadio extends Radio {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $info = parent::getInfo();
    $info['#theme_wrappers'] = ['form_element_type'];
    return $info;
  }

}
