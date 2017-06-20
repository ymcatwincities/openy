<?php

namespace Drupal\rabbit_hole\Exception;

/**
 * Class InvalidBehaviorSettingException.
 *
 * @package Drupal\rabbit_hole
 */
class InvalidBehaviorSettingException extends \Exception {

  private $setting;

  /**
   * Constructor.
   */
  public function __construct($setting) {
    parent::__construct();
    $this->setting = $setting;
  }

  /**
   * Get the invalid setting.
   */
  public function getSetting() {
    return $this->setting();
  }

}
