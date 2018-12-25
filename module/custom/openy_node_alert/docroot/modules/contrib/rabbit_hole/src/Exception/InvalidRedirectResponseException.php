<?php

namespace Drupal\rabbit_hole\Exception;

/**
 * Class InvalidRedirectResponseException.
 *
 * @package Drupal\rabbit_hole
 */
class InvalidRedirectResponseException extends \Exception {

  /**
   * Constructor.
   */
  public function __construct($message = NULL, $code = 0, Exception $previous = NULL) {
    parent::__construct($message, $code, $previous);
  }

}
