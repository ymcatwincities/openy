<?php

namespace Drupal\mindbody;

/**
 * Mindbody Service Manager interface.
 *
 * @package Drupal\mindbody
 */
interface MindbodyClientInterface {

  /**
   * Make request to MindBody API.
   *
   * @param string $service
   *   Service name. Example: 'SiteService'.
   * @param string $endpoint
   *   Endpoint name. Example: 'GetLocations'.
   * @param array $params
   *   Array of parameters.
   *
   * @return \stdClass
   *   A result.
   */
  public function call($service, $endpoint, array $params = []);

}
