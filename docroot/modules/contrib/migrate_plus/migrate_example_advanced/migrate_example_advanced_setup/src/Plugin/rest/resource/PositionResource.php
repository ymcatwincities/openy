<?php

/**
 * @file
 * Contains \Drupal\migrate_example_advanced_setup\Plugin\rest\resource\PositionResource.
 */

namespace Drupal\migrate_example_advanced_setup\Plugin\rest\resource;

use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;

/**
 * Represents positions as resources.
 *
 * @RestResource(
 *   id = "migrate_example_advanced_position",
 *   label = @Translation("Advanced migration example - Position data"),
 *   uri_paths = {
 *     "canonical" = "/migrate_example_advanced_position"
 *   }
 * )
 */
class PositionResource extends ResourceBase {

  /**
   * Responds to GET requests.
   *
   * @return \Drupal\rest\ResourceResponse
   *   The response containing the position data.
   */
  public function get() {
    $position1 = ['sourceid' => 'wine_taster', 'name' => 'Wine Taster'];
    $position2 = ['sourceid' => 'vintner', 'name' => 'Vintner'];
    $data = ['position' => [$position1, $position2]];

    $response = new ResourceResponse($data, 200);
    return $response;
  }

}
