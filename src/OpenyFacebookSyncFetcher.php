<?php

namespace Drupal\openy_facebook_sync;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Class OpenyFacebookSyncFetcher
 *
 * @package Drupal\openy_facebook_sync
 */
class OpenyFacebookSyncFetcher {

  use StringTranslationTrait;

  /**
   * Cache time for fetcher.
   */
  const CACHE_TIME = 3600;

  /**
   * Wrapper to be used for sharing data between steps.
   *
   * @var \Drupal\openy_facebook_sync\OpenyFacebookSyncWrapperInterface
   */
  private $wrapper;

  /**
   * Predefined logger channel.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  private $logger;

  /**
   * Cache Backend.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  private $cacheBackend;

  /**
   * Fetcher constructor.
   *
   * @param \Drupal\openy_facebook_sync\OpenyFacebookSyncWrapperInterface $wrapper
   *   Data wrapper.
   * @param \Drupal\Core\Logger\LoggerChannelInterface $loggerChannel
   *   Logger Channel.
   * @param \Drupal\Core\Cache\CacheBackendInterface
   *   Cache Backend.
   */
  public function __construct(OpenyFacebookSyncWrapperInterface $wrapper, LoggerChannelInterface $loggerChannel, CacheBackendInterface $cacheBackend) {
    $this->wrapper = $wrapper;
    $this->logger = $loggerChannel;
    $this->cacheBackend = $cacheBackend;
  }

  /**
   * {@inheritdoc}
   */
  public function fetch(array $args) {
    $cid = __METHOD__;
    if ($cache = $this->cacheBackend->get($cid)) {
      $data = $cache->data;
    }
    else {
      $data = [];

      // @todo Get data here.

      $this->cacheBackend->set($cid, $data, REQUEST_TIME + self::CACHE_TIME);
    }

    $this->wrapper->setSourceData($data);
  }

}
