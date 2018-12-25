<?php

/**
 * @file
 * Contains \Drupal\memcache\PersistentMemcacheLockBackend.
 */

namespace Drupal\memcache\Lock;

use Drupal\memcache\DrupalMemcacheInterface;

class PersistentMemcacheLockBackend extends MemcacheLockBackend {

  /**
   * Constructs a new MemcacheLockBackend.
   *
   * @param \Drupal\Memcache\DrupalMemcacheInterface $memcache
   */
  public function __construct($bin, DrupalMemcacheInterface $memcache) {
    $this->bin = $bin;

    // Do not call the parent constructor to avoid registering a shutdwon
    // function that will release all locks at the end of the request.
    $this->memcache = $memcache;
    // Set the lockId to a fixed string to make the lock ID the same across
    // multiple requests. The lock ID is used as a page token to relate all the
    // locks set during a request to each other.
    // @see \Drupal\Core\Lock\LockBackendInterface::getLockId()
    $this->lockId = 'persistent';
  }

}
