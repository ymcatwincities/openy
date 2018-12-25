<?php

namespace Drupal\acquia_connector;

use \Exception;

/**
 * Class ConnectorException.
 *
 * @package Drupal\acquia_connector
 */
class ConnectorException extends Exception {
  private $custom = [];

  /**
   * Construction method.
   *
   * @param string $message
   *   Exception message.
   * @param int $code
   *   Execption code.
   * @param array $custom
   *   Exception messages as key => value.
   * @param Exception $previous
   *   The previous exception used for the exception chaining. Since 5.3.0.
   */
  public function __construct($message, $code = 0, $custom = [], Exception $previous = NULL) {
    parent::__construct($message, $code, $previous);
    $this->custom = $custom;
  }

  /**
   * Check is customized.
   *
   * @return bool
   *   TRUE if not custom message is not empty, FALSE otherwise.
   */
  public function isCustomized() {
    return !empty($this->custom);
  }

  /**
   * Set custom message.
   *
   * @param mixed $value
   *   Custom message value.
   * @param string $key
   *   Custom message key.
   */
  public function setCustomMessage($value, $key = 'message') {
    $this->custom[$key] = $value;
  }

  /**
   * Get custom message.
   *
   * @param string $key
   *   Custom message key.
   * @param bool $fallback
   *   Default is TRUE. Return standard code or message.
   *
   * @return mixed
   *   Custom message of FALSE;
   */
  public function getCustomMessage($key = 'message', $fallback = TRUE) {
    if (isset($this->custom[$key])) {
      return $this->custom[$key];
    }
    if (!$fallback) {
      return FALSE;
    }
    switch ($key) {
      case 'code':
        return $this->getCode();

      break;
      case 'message':
        return $this->getMessage();

      break;
    }
    return FALSE;
  }

  /**
   * Get all custom messages.
   *
   * @return array
   *   Array of custom messages.
   */
  public function getAllCustomMessages() {
    return $this->custom;
  }

}
