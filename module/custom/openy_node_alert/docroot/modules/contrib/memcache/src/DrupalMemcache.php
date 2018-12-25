<?php

/**
 * @file
 * Contains \Drupal\memcache\DrupalMemcache.
 */

namespace Drupal\memcache;

use Psr\Log\LogLevel;

/**
 * Class DrupalMemcache.
 */
class DrupalMemcache extends DrupalMemcacheBase {

  /**
   * {@inheritdoc}
   */
  public function __construct(DrupalMemcacheConfig $settings) {
    parent::__construct($settings);

    $this->memcache = new \Memcache();
  }

  /**
   * @{@inheritdoc}
   */
  public function addServer($server_path, $persistent = FALSE) {
    list($host, $port) = explode(':', $server_path);

    // Support unix sockets in the format 'unix:///path/to/socket'.
    if ($host == 'unix') {
      // When using unix sockets with Memcache use the full path for $host.
      $host = $server_path;
      // Port is always 0 for unix sockets.
      $port = 0;
    }

    // When using the PECL memcache extension, we must use ->(p)connect
    // for the first connection.
    return $this->connect($host, $port, $persistent);
  }

  /**
   * {@inheritdoc}
   */
  public function close() {
    $this->memcache->close();
  }

  /**
   * Connects to a memcache server.
   *
   * @param string $host
   * @param int $port
   * @param bool $persistent
   *
   * @return bool|mixed
   */
  protected function connect($host, $port, $persistent) {
    if ($persistent) {
      return @$this->memcache->pconnect($host, $port);
    }
    else {
      return @$this->memcache->connect($host, $port);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function set($key, $value, $exp = 0, $flag = FALSE) {
    $full_key = $this->key($key);
    return $this->memcache->set($full_key, $value, $flag, $exp);
  }

  /**
   * {@inheritdoc}
   */
  public function getMulti(array $keys) {
    $full_keys = array();

    foreach ($keys as $cid) {
      $full_key = $this->key($cid);
      $full_keys[$cid] = $full_key;
    }

    $results = $this->memcache->get($full_keys);

    // If $results is FALSE, convert it to an empty array.
    if (!$results) {
      $results = array();
    }

    // Convert the full keys back to the cid.
    $cid_results = array();

    // Order isn't guaranteed, so ensure the return order matches that
    // requested. So base the results on the order of the full_keys, as they
    // reflect the order of the $cids passed in.
    foreach (array_intersect($full_keys, array_keys($results)) as $cid => $full_key) {
      $cid_results[$cid] = $results[$full_key];
    }

    return $cid_results;
  }

}
