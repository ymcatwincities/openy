<?php

namespace Drupal\migrate_example_advanced_setup\Plugin\rest\resource;

use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;

/**
 * Provides varieties as two endpoints, one for reds and one for whites.
 *
 * @RestResource(
 *   id = "migrate_example_advanced_variety_list",
 *   label = @Translation("Advanced migration example - Variety list of data"),
 *   uri_paths = {
 *     "canonical" = "/migrate_example_advanced_variety_list"
 *   }
 * )
 */
class VarietyList extends ResourceBase {

  /**
   * Responds to GET requests.
   *
   * @return \Drupal\rest\ResourceResponse
   *   The response containing the requested variety data.
   */
  public function get() {
    $data['items'] = ['retsina', 'trebbiano', 'valpolicella', 'bardolino'];

    $response = new ResourceResponse($data, 200);
    return $response;
  }

  /**
   * {@inheritdoc}
   */
  public function permissions() {
    // Remove permissions so the resource is available to all.
    return [];
  }

}
