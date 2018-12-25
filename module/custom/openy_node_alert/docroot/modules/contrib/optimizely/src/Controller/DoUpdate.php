<?php

namespace Drupal\optimizely\Controller;

/**
 * Form builder class for project adds and updates.
 */
class DoUpdate {

  /**
   * Form builder for route optimizely.add_update.oid .
   */
  public static function buildUpdateForm($oid) {
    return \Drupal::formBuilder()->getForm('Drupal\optimizely\Form\AddUpdateForm', $oid);
  }

}
