<?php

namespace Drupal\ymca_google;

use Drupal\Core\Logger\LoggerChannelInterface;
use GuzzleHttp\Client;

/**
 * Class IcsFetcher.
 *
 * @package Drupal\ymca_google
 */
class IcsFetcher implements IcsFetcherInterface {

  /**
   * Wrapper.
   *
   * @var \Drupal\ymca_google\GcalGroupexWrapperInterface
   */
  protected $wrapper;

  /**
   * The logger channel.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * Guzzle http client.
   *
   * @var \GuzzleHttp\Client
   */
  protected $client;

  /**
   * IcsFetcher constructor.
   *
   * @param \Drupal\ymca_google\GcalGroupexWrapperInterface $wrapper
   *   Data wrapper.
   * @param \Drupal\Core\Logger\LoggerChannelInterface $logger
   *   The logger channel.
   * @param \GuzzleHttp\Client $client
   *   Guzzle http client.
   */
  public function __construct(GcalGroupexWrapperInterface $wrapper, LoggerChannelInterface $logger, Client $client) {
    $this->wrapper = $wrapper;
    $this->looger = $logger;
    $this->client = $client;
  }

  /**
   * {@inheritdoc}
   */
  public function fetch(array $args) {
  }

}
