<?php

/**
 * @file
 * Contains \Drupal\memcache\DrupalMemcached.
 */

namespace Drupal\memcache;

/**
 * Class DrupalMemcached.
 */
class DrupalMemcached extends DrupalMemcacheBase {

  /**
   * {@inheritdoc}
   */
  public function __construct(DrupalMemcacheConfig $settings) {
    parent::__construct($settings);

    $this->memcache = new \Memcached();

    $default_opts = array(
      \Memcached::OPT_COMPRESSION => FALSE,
      \Memcached::OPT_DISTRIBUTION => \Memcached::DISTRIBUTION_CONSISTENT,
    );
    foreach ($default_opts as $key => $value) {
      $this->memcache->setOption($key, $value);
    }
    // See README.txt for setting custom Memcache options when using the
    // memcached PECL extension.
    foreach ($this->settings->get('options', []) as $key => $value) {
      $this->memcache->setOption($key, $value);
    }

    // SASL configuration to authenticate with Memcached.
    // Note: this only affects the Memcached PECL extension.
    if ($sasl_config = $this->settings->get('sasl', [])) {
      $this->memcache->setSaslAuthData($sasl_config['username'], $sasl_config['password']);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function addServer($server_path, $persistent = FALSE) {
    list($host, $port) = explode(':', $server_path);

    if ($host == 'unix') {
      // Memcached expects just the path to the socket without the protocol
      $host = substr($server_path, 7);
      // Port is always 0 for unix sockets.
      $port = 0;
    }

    return $this->memcache->addServer($host, $port, $persistent);
  }

  /**
   * {@inheritdoc}
   */
  public function close() {
    $this->memcache->quit();
  }

  /**
   * {@inheritdoc}
   */
  public function set($key, $value, $exp = 0, $flag = FALSE) {
    $full_key = $this->key($key);
    return $this->memcache->set($full_key, $value, $exp);
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

    if (PHP_MAJOR_VERSION === 7) {
      $results = $this->memcache->getMulti($full_keys, \Memcached::GET_PRESERVE_ORDER);
    } else {
      $cas_tokens = NULL;
      $results = $this->memcache->getMulti($full_keys, $cas_tokens, \Memcached::GET_PRESERVE_ORDER);
    }

    // If $results is FALSE, convert it to an empty array.
    if (!$results) {
      $results = array();
    }

    // Convert the full keys back to the cid.
    $cid_results = array();
    $cid_lookup = array_flip($full_keys);

    foreach (array_filter($results) as $key => $value) {
      $cid_results[$cid_lookup[$key]] = $value;
    }

    return $cid_results;
  }

}
