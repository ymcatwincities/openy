<?php

namespace Drupal\openy_facebook_sync;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use DateTime;
use DateTimeZone;

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
   * Returns app_id array from module settings.
   *
   * @return array
   *   Application IDs defined in module settings.
   */
  protected function getAppIds() {
    $app_id = $this->configFactory
      ->get('openy_facebook_sync.settings')
      ->get('app_id');
    return $app_id;
  }

  /**
   * Returns Fetch Passed Events option value.
   *
   * @return bool
   *   Fetch Passed Events.
   */
  public function getFetchPassedEventsOption() {
    return $this->configFactory
      ->get('openy_facebook_sync.settings')
      ->get('fetch_passed_events');
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
      $fb_objects = $this->facebook;
      foreach ($fb_objects as $fb) {
        /* @var \Facebook\Facebook $fb */
        $appid = $fb->getApp()->getId();
        $result = $fb->sendRequest('GET', $appid . "/events");
        $events = $result->getGraphEdge();
        // Array of events from all pages.
        $all_events = [];
        if ($fb->next($events)) {
          // Code executed when next page is available.
          $all_events = array_merge($events->asArray(), $all_events);
          while ($events = $fb->next($events)) {
            // Loop to save events from all pages.
            $all_events = array_merge($events->asArray(), $all_events);
          }
        }
        else {
          // Code executed when next page not available.
          $all_events = array_merge($events->asArray(), $all_events);
        }

        // All events array fetching.
        foreach ($all_events as $event) {
          if (!$this->getFetchPassedEventsOption() && isset($event['end_time'])) {
            // As there is no way to filter out ended events via request to FB API,
            // skip events that have ended comparing to request time.
            $site_timezone = new DateTimeZone($this->configFactory->get('system.date')
              ->get('timezone')['default']);
            $current_date = DateTime::createFromFormat('U', REQUEST_TIME, $site_timezone);
            if ($event['end_time']->setTimezone($site_timezone) < $current_date) {
              continue;
            }
          }
          $data[] = $event;
        }
      }
      $this->cacheBackend->set($cid, $data, REQUEST_TIME + self::CACHE_TIME);
    }

    $this->wrapper->setSourceData($data);
  }

}
