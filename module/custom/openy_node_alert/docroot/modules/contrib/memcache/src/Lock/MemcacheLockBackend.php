<?php

/**
 * @file
 * Contains \Drupal\memcache\MemcacheLockBackend.
 */

namespace Drupal\memcache\Lock;

use Drupal\Core\Lock\LockBackendAbstract;
use Drupal\memcache\DrupalMemcacheInterface;

/**
 * Defines a Memcache lock backend.
 */
class MemcacheLockBackend extends LockBackendAbstract {

  /**
   * An array of currently acquired locks.
   *
   * @var array
   */
  protected $locks = array();

  /**
   * The bin name for this lock.
   *
   * @var string
   */
  protected $bin;

  /**
   * The memcache wrapper object.
   *
   * @var \Drupal\memcache\DrupalMemcacheInterface
   */
  protected $memcache;

  /**
   * Constructs a new MemcacheLockBackend.
   */
  public function __construct($bin, DrupalMemcacheInterface $memcache) {
    $this->bin = $bin;
    $this->memcache = $memcache;

    // __destruct() is causing problems with garbage collections, register a
    // shutdown function instead.
    drupal_register_shutdown_function([$this, 'releaseAll']);
  }

  /**
   * {@inheritdoc}
   */
  public function acquire($name, $timeout = 30.0) {
    // Ensure that the timeout is at least 1 sec. This is a limitation imposed
    // by memcached.
    $timeout = (int) max($timeout, 1);

    $lock_id = $this->getLockId();
    $key = $this->getKey($name);

    if (isset($this->locks[$name])) {
      // Try to extend the expiration of a lock we already acquired.
      $success = !$this->lockMayBeAvailable($name) && $this->memcache->set($key, $lock_id, $timeout);

      if (!$success) {
        // The lock was broken.
        unset($this->locks[$name]);
      }

      return $success;
    }
    else {
      if ($this->lockMayBeAvailable($name)) {
        $success = $this->memcache->set($key, $lock_id, $timeout);

        if (!$success) {
          return FALSE;
        }

        // We track all acquired locks in the global variable, if successful.
        $this->locks[$name] = TRUE;
      }
      else {
        return FALSE;
      }
    }

    return isset($this->locks[$name]);
  }

  /**
   * {@inheritdoc}
   */
  public function lockMayBeAvailable($name) {
    return !$this->memcache->get($this->getKey($name));
  }

  /**
   * {@inheritdoc}
   */
  public function release($name) {
    $this->memcache->delete($this->getKey($name));
    // We unset unconditionally since caller assumes lock is released anyway.
    unset($this->locks[$name]);
  }

  /**
   * {@inheritdoc}
   */
  public function releaseAll($lock_id = NULL) {
    if (empty($lock_id)) {
      $lock_id = $this->getLockId();
    }

    foreach ($this->locks as $name => $id) {
      $key = $this->getKey($name);
      $value = $this->memcache->get($key);

      if ($value == $lock_id) {
        $this->memcache->delete($key);
      }
    }

    $this->locks = [];
  }

  /**
   * Gets a storage key based on the lock name.
   */
  protected function getKey($name) {
    return 'lock:' . $this->bin . ':' . $name;
  }

}
