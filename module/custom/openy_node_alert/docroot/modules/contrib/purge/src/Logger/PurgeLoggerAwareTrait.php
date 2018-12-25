<?php

namespace Drupal\purge\Logger;

use \Psr\Log\LoggerAwareTrait;

/**
 * Provides logging services for purge components.
 */
trait PurgeLoggerAwareTrait {
  use LoggerAwareTrait;

  /**
   * {@inheritdoc}
   */
  public function logger() {
    if (is_null($this->logger)) {
      throw new \LogicException('Logger unavailable, call ::setLogger().');
    }
    return $this->logger;
  }

}
