<?php

namespace Drupal\ymca_mindbody;

/**
 * Interface MindbodyProxyInterface.
 *
 * @package Drupal\ymca_mindbody
 */
interface MindbodyProxyInterface {

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
