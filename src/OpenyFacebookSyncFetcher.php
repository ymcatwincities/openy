<?php

namespace Drupal\openy_facebook_sync;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Class OpenyFacebookSyncFetcher.
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
   * Config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Fetcher constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   Used for accessing Drupal configuration.
   * @param \Drupal\openy_facebook_sync\OpenyFacebookSyncWrapperInterface $wrapper
   *   Data wrapper.
   * @param \Drupal\Core\Logger\LoggerChannelInterface $loggerChannel
   *   Logger Channel.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cacheBackend
   *   Cache Backend.
   * @param OpenyFacebookSyncFactory $facebook_factory
   *   Facebook factory.
   */
  public function __construct(ConfigFactoryInterface $config_factory, OpenyFacebookSyncWrapperInterface $wrapper, LoggerChannelInterface $loggerChannel, CacheBackendInterface $cacheBackend, OpenyFacebookSyncFactory $facebook_factory) {
    $this->configFactory = $config_factory;
    $this->wrapper = $wrapper;
    $this->logger = $loggerChannel;
    $this->cacheBackend = $cacheBackend;
    $this->facebook = $facebook_factory->getFacebook();
  }

  /**
   * Returns app_id from module settings.
   *
   * @return string
   *   Application ID defined in module settings.
   */
  protected function getAppId() {
    $app_id = $this->configFactory
      ->get('openy_facebook_sync.settings')
      ->get('app_id');
    return $app_id;
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
      $appid = $this->getAppId();
      $result = $this->facebook->sendRequest('GET', $appid . "/events");
      $body = $result->getDecodedBody();
      foreach ($body['data'] as $event) {
        $data[] = $event;
      }

      $this->cacheBackend->set($cid, $data, REQUEST_TIME + self::CACHE_TIME);
    }

    $this->wrapper->setSourceData($data);
  }

}
