<?php

namespace Drupal\activenet_registration;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Url;
use Drupal\activenet\ActivenetClient;
use Drupal\openy_socrates\OpenyCronServiceInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\Cookie\SetCookie;

/**
 * Class DataStorage.
 */
class DataStorage implements DataStorageInterface, OpenyCronServiceInterface {

  /**
   * Activenet Client
   * 
   * @var \Drupal\activenet\ActivenetClient
   */
  protected $client;

  /**
   * Cache backend.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * {@inheritdoc}
   */
  public function runCronServices() {
    $this->resetCache();
    $this->warmCache();
    \Drupal::logger('ActiveNet Registration')->info("Cron service run succeeded. New API call made.");
  }

  /**
   * DataStorage constructor.
   *
   * @param \Drupal\activenet\ActivenetClient $client
   *   Daxko client.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   Cache backend.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   */
  public function __construct(ActivenetClient $client, CacheBackendInterface $cache, ConfigFactoryInterface $config_factory) {
    $this->client = $client;
    $this->cache = $cache;
    $this->configFactory = $config_factory;
  }

  /**
   * Delete all caches.
   */
  public function resetCache() {
    $this->cache->deleteAll();
  }

  /**
   * Warm up all cache.
   *
   * @ingroup cache
   */
  public function warmCache() {
    $this->getSites();
    $this->getProgramTypes();
    $this->getActivityTypes();
    $this->getCategories();
    $this->getOtherCategories();
  }

  public function getSites() {
    $cid = __METHOD__;
    if ($cache = $this->cache->get($cid)) {
      return $cache->data;
    }

    $sites = $this->client->getSites();
    $this->cache->set($cid, $sites);

    return $sites;
  }

  public function getProgramTypes() {
    $cid = __METHOD__;
    if ($cache = $this->cache->get($cid)) {
      return $cache->data;
    }

    $programs = $this->client->getFlexRegProgramTypes();
    $this->cache->set($cid, $programs);

    return $programs;
  }

  public function getActivityTypes() {
    $cid = __METHOD__;
    if ($cache = $this->cache->get($cid)) {
      return $cache->data;
    }

    $sites = $this->client->getActivityTypes();
    $this->cache->set($cid, $sites);

    return $sites;
  }

  public function getCategories() {
    $cid = __METHOD__;
    if ($cache = $this->cache->get($cid)) {
      return $cache->data;
    }

    $sites = $this->client->getActivityCategories();
    $this->cache->set($cid, $sites);

    return $sites;
  }

  public function getOtherCategories() {
    $cid = __METHOD__;
    if ($cache = $this->cache->get($cid)) {
      return $cache->data;
    }

    $sites = $this->client->getActivityOtherCategories();
    $this->cache->set($cid, $sites);

    return $sites;
  }

}
