<?php

namespace Drupal\activenet;

/**
 * Interface ActivenetClientFactoryInterface.
 *
 * @package Drupal\activenet
 */
interface ActivenetClientFactoryInterface {

  /**
   * Returns Activenet client.
   *
   * @return \GuzzleHttp\Client
   *   The http client.
   */
  public function get();

}
