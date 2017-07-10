<?php

namespace Drupal\acquia_connector;

use Drupal\Core\Logger\RfcLoggerTrait;
use Psr\Log\LoggerInterface;

/**
 * Class CronService.
 *
 * @package Drupal\acquia_connector
 */
class CronService implements LoggerInterface {
  use RfcLoggerTrait;

  /**
   * {@inheritdoc}
   */
  public function log($level, $message, array $context = array()) {
    // Make sure that even when cron failures prevent hook_cron() from being
    // called, we still send out a heartbeat.
    if (!empty($context['channel']) && ($context['channel'] == 'cron') && ($message == 'Attempting to re-run cron while it is already running.')) {
      $subscription = new Subscription();
      $subscription->update();
    }
  }

}
