<?php

namespace Drupal\purge\Logger;

use Drupal\Core\DependencyInjection\ServiceProviderInterface;
use Drupal\Core\DependencyInjection\ServiceModifierInterface;

/**
 * Describes a factory that creates LoggerChannelPartInterface instances.
 */
interface LoggerChannelPartFactoryInterface extends ServiceProviderInterface, ServiceModifierInterface {

  /**
   * Create a channel part instance.
   *
   * @param string $id
   *   The identifier of the channel part.
   * @param int[] $grants
   *   Unassociative array of RFC 5424 log types. Each passed type grants the
   *   channel permission to log that type of message, without specific
   *   permissions the logger will stay silent for that type.
   *
   *   Grants available:
   *    - \Drupal\Core\Logger\RfcLogLevel::EMERGENCY
   *    - \Drupal\Core\Logger\RfcLogLevel::ALERT
   *    - \Drupal\Core\Logger\RfcLogLevel::CRITICAL
   *    - \Drupal\Core\Logger\RfcLogLevel::ERROR
   *    - \Drupal\Core\Logger\RfcLogLevel::WARNING
   *    - \Drupal\Core\Logger\RfcLogLevel::NOTICE
   *    - \Drupal\Core\Logger\RfcLogLevel::INFO
   *    - \Drupal\Core\Logger\RfcLogLevel::DEBUG
   *
   * @return \Drupal\purge\Logger\LoggerChannelPartInterface.
   */
  public function create($id, array $grants = []);

}
