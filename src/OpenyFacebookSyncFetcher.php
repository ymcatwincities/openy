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
   * Facebook.
   *
   * @var \Facebook\Facebook
   */
  private $facebook;

  /**
   * Fetcher constructor.
   *
   * @param \Drupal\openy_facebook_sync\OpenyFacebookSyncWrapperInterface $wrapper
   *   Data wrapper.
   * @param \Drupal\Core\Logger\LoggerChannelInterface $loggerChannel
   *   Logger Channel.
   * @param \Drupal\Core\Cache\CacheBackendInterface
   *   Cache Backend.
   * @param OpenyFacebookSyncFactory
   *   Facebook factory.
   */
  public function __construct(OpenyFacebookSyncWrapperInterface $wrapper, LoggerChannelInterface $loggerChannel, CacheBackendInterface $cacheBackend, OpenyFacebookSyncFactory $facebook_factory) {
    $this->wrapper = $wrapper;
    $this->logger = $loggerChannel;
    $this->cacheBackend = $cacheBackend;
    $this->facebook = $facebook_factory->getFacebook();
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

      // @todo Implement pager|filtering passed events.
      // @todo Use page ID from config.
      $result = $this->facebook->sendRequest('GET', "71944364922/events");
      $body = $result->getDecodedBody();
      foreach ($body['data'] as $event) {
        $data[] = $event;
      }

      $this->cacheBackend->set($cid, $data, REQUEST_TIME + self::CACHE_TIME);
    }

    $this->wrapper->setSourceData($data);
  }

}
