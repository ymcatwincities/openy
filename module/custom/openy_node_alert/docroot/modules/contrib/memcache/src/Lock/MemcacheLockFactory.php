<?php

/**
 * @file
 * Contains \Drupal\memcache\Lock\MemcacheLockFactory.
 */

namespace Drupal\memcache\Lock;

use Drupal\memcache\DrupalMemcacheFactory;

/**
 * THe memcache lock factory.
 */
class MemcacheLockFactory {

  /**
   * The bin name for this lock.
   *
   * @var string
   */
  protected $bin = 'semaphore';

  /**
   * The memcache factory.
   *
   * @var \Drupal\memcache\DrupalMemcacheFactory
   */
  protected $factory;

  /**
   * Constructs a new MemcacheLockBackend.
   *
   * @param \Drupal\memcache\DrupalMemcacheFactory $memcache_factory
   */
  public function __construct(DrupalMemcacheFactory $memcache_factory) {
    $this->factory = $memcache_factory;
  }

  /**
   * Gets a lock backend instance.
   *
   * @return \Drupal\Core\Lock\LockBackendInterface
   */
  public function get() {
    return new MemcacheLockBackend($this->bin, $this->factory->get($this->bin));
  }

  /**
   * Gets a persistent lock backend instance.
   *
   * @return \Drupal\Core\Lock\LockBackendInterface
   */
  public function getPersistent() {
    return new PersistentMemcacheLockBackend($this->bin, $this->factory->get($this->bin));
  }

}
