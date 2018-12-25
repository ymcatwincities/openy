<?php

namespace Drupal\purge\Logger;

use Psr\Log\LogLevel;
use Psr\Log\LoggerInterface;
use Drupal\Core\DependencyInjection\ServiceProviderBase;
use Drupal\Core\Logger\RfcLogLevel;
use Drupal\purge\Logger\LoggerChannelPart;

/**
 * Provides a subchannel whichs logs to a single main channel with permissions.
 */
class LoggerChannelPart implements LoggerChannelPartInterface {

  /**
   * The identifier of the channel part.
   *
   * @var string
   */
  protected $id = '';

  /**
   * Permitted RFC 5424 log types.
   *
   * @var int[]
   */
  protected $grants = [];

  /**
   * The single and central logger channel used by purge module(s).
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $loggerChannelPurge;

  /**
   * {@inheritdoc}
   */
  public function __construct(LoggerInterface $logger_channel_purge, $id, array $grants = []) {
    $this->id = $id;
    $this->grants = $grants;
    $this->loggerChannelPurge = $logger_channel_purge;
  }

  /**
   * {@inheritdoc}
   */
  public function getGrants() {
    return $this->grants;
  }

  /**
   * {@inheritdoc}
   */
  public function emergency($message, array $context = []) {
    if (in_array(RfcLogLevel::EMERGENCY, $this->grants)) {
      $this->log(LogLevel::EMERGENCY, $message, $context);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function alert($message, array $context = []) {
    if (in_array(RfcLogLevel::ALERT, $this->grants)) {
      $this->log(LogLevel::ALERT, $message, $context);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function critical($message, array $context = []) {
    if (in_array(RfcLogLevel::CRITICAL, $this->grants)) {
      $this->log(LogLevel::CRITICAL, $message, $context);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function error($message, array $context = []) {
    if (in_array(RfcLogLevel::ERROR, $this->grants)) {
      $this->log(LogLevel::ERROR, $message, $context);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function warning($message, array $context = []) {
    if (in_array(RfcLogLevel::WARNING, $this->grants)) {
      $this->log(LogLevel::WARNING, $message, $context);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function notice($message, array $context = []) {
    if (in_array(RfcLogLevel::NOTICE, $this->grants)) {
      $this->log(LogLevel::NOTICE, $message, $context);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function info($message, array $context = []) {
    if (in_array(RfcLogLevel::INFO, $this->grants)) {
      $this->log(LogLevel::INFO, $message, $context);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function debug($message, array $context = []) {
    if (in_array(RfcLogLevel::DEBUG, $this->grants)) {
      $this->log(LogLevel::DEBUG, $message, $context);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function log($level, $message, array $context = []) {
    $context += ['@purge_channel_part' => $this->id];
    $message = '@purge_channel_part: ' . $message;
    $this->loggerChannelPurge->log($level, $message, $context);
  }

}
