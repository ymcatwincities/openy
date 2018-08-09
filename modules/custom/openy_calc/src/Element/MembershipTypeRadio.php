<?php

namespace Drupal\openy_calc\Element;

use Drupal\Core\Render\Element\Radio;

/**
 * Provides a form element for a single radio button (membership type).
 *
 * @FormElement("membership_type_radio")
 */
class MembershipTypeRadio extends Radio {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $info = parent::getInfo();
    $info['#theme_wrappers'] = ['form_element_membership_type'];
    return $info;
  }

}
