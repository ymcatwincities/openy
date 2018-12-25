<?php

namespace Drupal\purge\Logger;

use \Psr\Log\LoggerAwareInterface;

/**
 * Describes logging services for purge components.
 */
interface PurgeLoggerAwareInterface extends LoggerAwareInterface {

  /**
   * Return the part logger.
   *
   * @throws \LogicException
   *   Thrown when the logger is unavailable.
   *
   * @return null|\Drupal\purge\Logger\LoggerChannelPartInterface
   */
  public function logger();

}
