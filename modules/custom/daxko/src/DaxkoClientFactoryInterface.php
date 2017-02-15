<?php

namespace Drupal\daxko;

/**
 * Interface DaxkoClientFactoryInterface.
 *
 * @package Drupal\daxko
 */
interface DaxkoClientFactoryInterface {

  /**
   * Returns Daxko client.
   *
   * @return \GuzzleHttp\Client
   *   The http client.
   */
  public function get();

}
