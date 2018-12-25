<?php

namespace Drupal\purge\Logger;

use Psr\Log\LoggerInterface;
use Drupal\Core\DependencyInjection\ServiceProviderBase;
use Drupal\purge\Logger\LoggerChannelPartFactoryInterface;
use Drupal\purge\Logger\LoggerChannelPart;

/**
 * Provides a factory that creates LoggerChannelPartInterface instances.
 */
class LoggerChannelPartFactory extends ServiceProviderBase implements LoggerChannelPartFactoryInterface {

  /**
   * The single and central logger channel used by purge module(s).
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $loggerChannelPurge;

  /**
   * Construct \Drupal\purge\Logger\LoggerChannelPartFactory.
   *
   * @param \Psr\Log\LoggerInterface $logger_channel_purge
   *   The single and central logger channel used by purge module(s).
   */
  public function __construct(LoggerInterface $logger_channel_purge) {
    $this->loggerChannelPurge = $logger_channel_purge;
  }

  /**
   * {@inheritdoc}
   */
  public function create($id, array $grants = []) {
    return new LoggerChannelPart($this->loggerChannelPurge, $id, $grants);
  }

}
