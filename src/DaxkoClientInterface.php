<?php

namespace Drupal\daxko;

use GuzzleHttp\Client;

/**
 * Interface DaxkoClientInterface.
 *
 * @package Drupal\daxko
 */
interface DaxkoClientInterface {

  /**
   * Returns data for the request.
   *
   * @param string $path
   *   Path.
   *
   * @return array
   *   Data.
   */
  public function getData($path);

}
