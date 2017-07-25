<?php

/**
 * @file
 * Contains \Drupal\memcache\DrupalMemcacheBase.
 */

namespace Drupal\memcache;

use Psr\Log\LogLevel;

/**
 * Class DrupalMemcacheBase.
 */
abstract class DrupalMemcacheBase implements DrupalMemcacheInterface {

  /**
   * The memcache config object.
   *
   * @var \Drupal\memcache\DrupalMemcacheConfig
   */
  protected $settings;

  /**
   * The memcache object.
   *
   * @var mixed
   *   E.g. \Memcache|\Memcached
   */
  protected $memcache;

  /**
   * The hash algorithm to pass to hash(). Defaults to 'sha1'
   *
   * @var string
   */
  protected $hashAlgorithm;

  /**
   * The prefix memcache key for all keys.
   *
   * @var string
   */
  protected $prefix;

  /**
   * Constructs a DrupalMemcacheBase object.
   *
   * @param \Drupal\memcache\DrupalMemcacheConfig
   *   The memcache config object.
   */
  public function __construct(DrupalMemcacheConfig $settings) {
    $this->settings = $settings;

    $this->hashAlgorithm = $this->settings->get('key_hash_algorithm', 'sha1');
    $this->prefix = $this->settings->get('key_prefix', '');
  }

  /**
   * {@inheritdoc}
   */
  public function get($key) {
    $full_key = $this->key($key);
    return $this->memcache->get($full_key);
  }

  /**
   * {@inheritdoc}
   */
  public function key($key) {
    $full_key = urlencode($this->prefix . '-' . $key);

    // Memcache only supports key lengths up to 250 bytes.  If we have generated
    // a longer key, we shrink it to an acceptable length with a configurable
    // hashing algorithm. Sha1 was selected as the default as it performs
    // quickly with minimal collisions.
    if (strlen($full_key) > 250) {
      $full_key = urlencode(hash($this->hashAlgorithm, $this->prefix . '-' . $key));
    }

    return $full_key;
  }

  /**
   * {@inheritdoc}
   */
  public function delete($key) {
    $full_key = $this->key($key);
    return $this->memcache->delete($full_key, 0);
  }

  /**
   * {@inheritdoc}
   */
  public function flush() {
    $this->memcache->flush();
  }

}
