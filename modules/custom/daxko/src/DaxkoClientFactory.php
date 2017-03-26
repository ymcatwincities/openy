<?php

namespace Drupal\daxko;

/**
 * Class DaxkoClientFactory.
 *
 * @package Drupal\daxko
 */
class DaxkoClientFactory implements DaxkoClientFactoryInterface {

  /**
   * {@inheritdoc}
   */
  public function get() {
    $config = [
      'base_uri' => 'https://example.com/v1/',
      'auth' => ['user', 'pass'],
      'headers'  => ['Accept' => 'application/json'],
    ];
    return new DaxkoClient($config);
  }

}
