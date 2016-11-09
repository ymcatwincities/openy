<?php

namespace Drupal\daxko;

/**
 * Interface DaxkoClientInterface.
 *
 * @package Drupal\daxko
 */
interface DaxkoClientInterface {

  /**
   * Wrapper for 'request' method.
   *
   * @param string $method
   *   Method.
   * @param string $uri
   *   URI.
   * @param array $parameters
   *   Parameters.
   *
   * @return mixed
   *   Data.
   */
  public function makeRequest($method, $uri, array $parameters);

}
