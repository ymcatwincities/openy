<?php

namespace Drupal\optimizely;

class DoUpdate {
  public static function buildUpdateForm($oid) {
    return \Drupal::formBuilder()->getForm('Drupal\optimizely\AddUpdateForm', $oid);
  }
}
