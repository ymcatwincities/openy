<?php

/**
 * @file
 * Contains \Drupal\memcache\MemcacheBackendFactory.
 */

namespace Drupal\memcache;

use Drupal\Core\Lock\LockBackendInterface;
use Drupal\Core\Cache\CacheTagsChecksumInterface;

/**
 * Class DatabaseBackendFactory.
 */
class MemcacheBackendFactory {

  /**
   * The lock backend that should be used.
   *
   * @var \Drupal\Core\Lock\LockBackendInterface
   */
  protected $lock;

  /**
   * The settings object.
   *
   * @var \Drupal\memcache\DrupalMemcacheConfig
   */
  protected $settings;

  /**
   * The memcache factory object.
   *
   * @var \Drupal\memcache\DrupalMemcacheFactory
   */
  protected $memcacheFactory;

  /**
   * The cache tags checksum provider.
   *
   * @var \Drupal\Core\Cache\CacheTagsChecksumInterface
   */
  protected $checksumProvider;

  /**
   * Constructs the DatabaseBackendFactory object.
   *
   * @param \Drupal\Core\Lock\LockBackendInterface $lock
   * @param \Drupal\memcache\DrupalMemcacheConfig $settings
   * @param \Drupal\memcache\DrupalMemcacheFactory $memcache_factory
   */
  function __construct(LockBackendInterface $lock, DrupalMemcacheConfig $settings, DrupalMemcacheFactory $memcache_factory, CacheTagsChecksumInterface $checksum_provider) {
    $this->lock = $lock;
    $this->settings = $settings;
    $this->memcacheFactory = $memcache_factory;
    $this->checksumProvider = $checksum_provider;
  }

  /**
   * Gets MemcacheBackend for the specified cache bin.
   *
   * @param $bin
   *   The cache bin for which the object is created.
   *
   * @return \Drupal\memcache\MemcacheBackend
   *   The cache backend object for the specified cache bin.
   */
  public function get($bin) {
    return new MemcacheBackend(
      $bin,
      $this->memcacheFactory->get($bin),
      $this->lock,
      $this->settings,
      $this->checksumProvider
    );
  }

}
