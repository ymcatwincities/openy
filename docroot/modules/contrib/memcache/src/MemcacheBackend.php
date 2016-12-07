<?php

/**
 * @file
 * Contains \Drupal\memcache\MemcacheBackend.
 */

namespace Drupal\memcache;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Cache\CacheTagsChecksumInterface;
use Drupal\Core\Lock\LockBackendInterface;

/**
 * Defines a Memcache cache backend.
 */
class MemcacheBackend implements CacheBackendInterface {

  /**
   * The cache bin to use.
   *
   * @var string
   */
  protected $bin;

  /**
   * The lock count.
   *
   * @var int
   */
  protected $lockCount = 0;

  /**
   * The memcache wrapper object.
   *
   * @var \Drupal\memcache\DrupalMemcacheInterface
   */
  protected $memcache;

  /**
   * The lock backend that should be used.
   *
   * @var \Drupal\Core\Lock\LockBackendInterface
   */
  protected $lock;

  /**
   * The Settings instance.
   *
   * @var \Drupal\memcache\DrupalMemcacheConfig
   */
  protected $settings;

  /**
   * The cache tags checksum provider.
   *
   * @var \Drupal\Core\Cache\CacheTagsChecksumInterface
   */
  protected $checksumProvider;

  /**
   * Constructs a MemcacheBackend object.
   *\Drupal\Core\Site\Settings
   * @param string $bin
   *   The bin name.
   * @param \Drupal\memcache\DrupalMemcacheInterface $memcache
   *   The memcache object.
   * @param \Drupal\Core\Lock\LockBackendInterface $lock
   *   The lock backend.
   * @param \Drupal\memcache\DrupalMemcacheConfig $settings
   *   The settings instance.
   * @param \Drupal\Core\Cache\CacheTagsChecksumInterface $checksum_provider
   *   The cache tags checksum service.
   */
  public function __construct($bin, DrupalMemcacheInterface $memcache, LockBackendInterface $lock, DrupalMemcacheConfig $settings, CacheTagsChecksumInterface $checksum_provider) {
    $this->bin = $bin;
    $this->memcache = $memcache;
    $this->lock = $lock;
    $this->settings = $settings;
    $this->checksumProvider = $checksum_provider;
  }

  /**
   * {@inheritdoc}
   */
  public function get($cid, $allow_invalid = FALSE) {
    $cids = array($cid);
    $cache = $this->getMultiple($cids, $allow_invalid);
    return reset($cache);
  }

  /**
   * {@inheritdoc}
   */
  public function getMultiple(&$cids, $allow_invalid = FALSE) {
    $keys = array_map(function($cid) {
      return $this->key($cid);
    }, $cids);

    $cache = $this->memcache->getMulti($keys);
    $fetched = [];

    foreach ($cache as $key => $result) {
      if ($this->valid($result->cid, $result) || $allow_invalid) {
        // Add it to the fetched items to diff later.
        $fetched[$result->cid] = $result;
      }
    }

    // Remove items from the referenced $cids array that we are returning,
    // per comment in Drupal\Core\Cache\CacheBackendInterface::getMultiple().
    $cids = array_diff($cids, array_keys($fetched));

    return $fetched;
  }

  /**
   * Determines if the cache item is valid.
   *
   * This also alters the valid property of the cache item itself.
   *
   * @param string $cid
   *   The cache ID.
   * @param \stdClass $cache
   *   The cache item.
   *
   * @return bool
   */
  protected function valid($cid, \stdClass $cache) {
    $lock_key = "memcache_$cid:$this->bin";
    $cache->valid = FALSE;

    if ($cache) {
      // Items that have expired are invalid.
      if (isset($cache->expire) && ($cache->expire != CacheBackendInterface::CACHE_PERMANENT) && ($cache->expire <= REQUEST_TIME)) {
        // If the memcache_stampede_protection variable is set, allow one
        // process to rebuild the cache entry while serving expired content to
        // the rest.
        if ($this->settings->get('stampede_protection', FALSE)) {
          // The process that acquires the lock will get a cache miss, all
          // others will get a cache hit.
          if (!$this->lock->acquire($lock_key, $this->settings->get('stampede_semaphore', 15))) {
            $cache->valid = TRUE;
          }
        }
      }
      else {
        $cache->valid = TRUE;
      }
    }
    // On cache misses, attempt to avoid stampedes when the
    // memcache_stampede_protection variable is enabled.
    else {
      if ($this->settings->get('stampede_protection', FALSE) && !$this->lock->acquire($lock_key, $this->settings->get('stampede_semaphore', 15))) {
        // Prevent any single request from waiting more than three times due to
        // stampede protection. By default this is a maximum total wait of 15
        // seconds. This accounts for two possibilities - a cache and lock miss
        // more than once for the same item. Or a cache and lock miss for
        // different items during the same request.
        // @todo: it would be better to base this on time waited rather than
        // number of waits, but the lock API does not currently provide this
        // information. Currently the limit will kick in for three waits of 25ms
        // or three waits of 5000ms.
        $this->lockCount++;
        if ($this->lockCount <= $this->settings->get('stampede_wait_limit', 3)) {
          // The memcache_stampede_semaphore variable was used in previous
          // releases of memcache, but the max_wait variable was not, so by
          // default divide the semaphore value by 3 (5 seconds).
         $this->lock->wait($lock_key, $this->settings->get('stampede_wait_time', 5));
          $cache = $this->get($cid);
        }
      }
    }

    // Check if invalidateTags() has been called with any of the items's tags.
    if (!$this->checksumProvider->isValid($cache->checksum, $cache->tags)) {
      $cache->valid = FALSE;
    }

    return (bool) $cache->valid;
  }

  /**
   * {@inheritdoc}
   */
  public function set($cid, $data, $expire = CacheBackendInterface::CACHE_PERMANENT, array $tags = array()) {
    assert('\Drupal\Component\Assertion\Inspector::assertAllStrings($tags)');
    $tags = array_unique($tags);
    // Sort the cache tags so that they are stored consistently.
    sort($tags);

    // Create new cache object.
    $cache = new \stdClass();
    $cache->cid = $cid;
    $cache->data = is_object($data) ? clone $data : $data;
    $cache->created = round(microtime(TRUE), 3);
    $cache->expire = $expire;
    $cache->tags = $tags;
    $cache->checksum = $this->checksumProvider->getCurrentChecksum($tags);

    // Cache all items permanently. We handle expiration in our own logic.
    return $this->memcache->set($this->key($cid), $cache);
  }

  /**
   * {@inheritdoc}
   */
  public function setMultiple(array $items) {
    foreach ($items as $cid => $item) {
      $item += array(
        'expire' => CacheBackendInterface::CACHE_PERMANENT,
        'tags' => array(),
      );

      $this->set($cid, $item['data'], $item['expire'], $item['tags']);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function delete($cid) {
    $this->memcache->delete($this->key($cid));
  }

  /**
   * {@inheritdoc}
   */
  public function deleteMultiple(array $cids) {
    foreach ($cids as $cid) {
      $this->memcache->delete($this->key($cid));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function deleteAll() {
    // Invalidate all keys, as we can't actually delete all?
    $this->invalidateAll();
  }

  /**
   * {@inheritdoc}
   */
  public function invalidate($cid) {
    $this->invalidateMultiple((array) $cid);
  }

  /**
   * Marks cache items as invalid.
   *
   * Invalid items may be returned in later calls to get(), if the
   * $allow_invalid argument is TRUE.
   *
   * @param array $cids
   *   An array of cache IDs to invalidate.
   *
   * @see Drupal\Core\Cache\CacheBackendInterface::deleteMultiple()
   * @see Drupal\Core\Cache\CacheBackendInterface::invalidate()
   * @see Drupal\Core\Cache\CacheBackendInterface::invalidateTags()
   * @see Drupal\Core\Cache\CacheBackendInterface::invalidateAll()
   */
  public function invalidateMultiple(array $cids) {
    foreach ($cids as $cid) {
      if ($item = $this->get($cid)) {
        $item->expire = REQUEST_TIME - 1;
        $this->memcache->set($this->key($cid), $item);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function invalidateAll() {
    $this->memcache->flush();
  }

  /**
   * {@inheritdoc}
   */
  public function invalidateTags(array $tags) {
    $this->checksumProvider->invalidateTags($tags);
  }

  /**
   * {@inheritdoc}
   */
  public function removeBin() {
    // Do nothing here too?
  }

  /**
   * {@inheritdoc}
   */
  public function garbageCollection() {
    // Memcache will invalidate items; That items memory allocation is then
    // freed up and reused. So nothing needs to be deleted/cleaned up here.
  }

  /**
   * (@inheritdoc)
   */
  public function isEmpty() {
    // We do not know so err on the safe side? Not sure if we can know this?
    return TRUE;
  }

  /**
   * Returns a cache key prefixed with the current bin.
   *
   * @param string $cid
   *
   * @return string
   */
  protected function key($cid) {
    return $this->bin . '-' . $cid;
  }

}
