<?php

namespace Drupal\mindbody_cache_proxy;

/**
 * Interface MindbodyProxyInterface.
 *
 * @package Drupal\ymca_mindbody
 */
interface MindbodyCacheProxyInterface {

  /**
   * Make request to MindBody API.
   *
   * @param string $service
   *   Service name. Example: 'SiteService'.
   * @param string $endpoint
   *   Endpoint name. Example: 'GetLocations'.
   * @param array $params
   *   Array of parameters.
   * @param bool $cache
   *   Indicates whether to use cache or call API directly.
   *
   * @return \stdClass
   *   A result.
   */
  public function call($service, $endpoint, array $params = [], $cache = TRUE);

}
