<?php

/**
 * @file
 * Contains \Drupal\migrate_example_advanced_setup\Plugin\rest\resource\VarietyItems.
 */

namespace Drupal\migrate_example_advanced_setup\Plugin\rest\resource;

use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;

/**
 * Provides varieties as two endpoints, one for reds and one for whites.
 *
 * @RestResource(
 *   id = "migrate_example_advanced_variety_items",
 *   label = @Translation("Advanced migration example - Variety data"),
 *   uri_paths = {
 *     "canonical" = "/migrate_example_advanced_variety_list/{variety}"
 *   }
 * )
 */
class VarietyItems extends ResourceBase {

  /**
   * Responds to GET requests.
   *
   * @param string $variety
   *   Machine name of the variety to retrieve.
   *
   * @return \Drupal\rest\ResourceResponse
   *   The response containing the requested variety data.
   */
  public function get($variety = NULL) {
    $varieties = [
      'retsina' => [
        'name' => 'Retsina',
        'parent' => 1,  // categoryid for 'white'.
        'details' => 'Greek',
      ],
      'trebbiano' => [
        'name' => 'Trebbiano',
        'parent' => 1,  // categoryid for 'white'.
        'details' => 'Italian',
      ],
      'valpolicella' => [
        'name' => 'Valpolicella',
        'parent' => 3,  // categoryid for 'red'.
        'details' => 'Italian Venoto region',
      ],
      'bardolino' => [
        'name' => 'Bardolino',
        'parent' => 3,  // categoryid for 'red'.
        'details' => 'Italian Venoto region',
      ],
    ];
    if (isset($varieties[$variety])) {
      $data = ['variety' => $varieties[$variety]];
    }
    else {
      $data = [];
    }

    $response = new ResourceResponse($data, 200);
    return $response;
  }

}
