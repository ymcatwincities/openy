<?php

namespace Drupal\migrate_example_advanced_setup\Plugin\rest\resource;

use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;

/**
 * Provides varieties as two endpoints, one for reds and one for whites.
 *
 * @RestResource(
 *   id = "migrate_example_advanced_variety_multiple",
 *   label = @Translation("Advanced migration example - Variety data"),
 *   uri_paths = {
 *     "canonical" = "/migrate_example_advanced_variety_multiple/{type}"
 *   }
 * )
 */
class VarietyMultiFiles extends ResourceBase {

  /**
   * Responds to GET requests.
   *
   * @param string $type
   *   'red', 'white', or NULL to return all varieties.
   *
   * @return \Drupal\rest\ResourceResponse
   *   The response containing the requested variety data.
   */
  public function get($type = NULL) {
    $data = [];
    if (strtolower($type) != 'white') {
      $data['variety'][] = [
        'name' => 'Amarone',
        'parent' => 3,  // categoryid for 'red'.
        'details' => 'Italian Venoto region',
        'attributes' => [
          'rich',
          'aromatic',
        ],
      ];
      $data['variety'][] = [
        'name' => 'Barbaresco',
        'parent' => 3,  // categoryid for 'red'.
        'details' => 'Italian Piedmont region',
        'attributes' => [
          'smoky',
          'earthy',
        ],
      ];
    }
    if (strtolower($type) != 'red') {
      $data['variety'][] = [
        'name' => 'Kir',
        'parent' => 1,  // categoryid for 'white'.
        'details' => 'French Burgundy region',
        'attributes' => [],
      ];
      $data['variety'][] = [
        'name' => 'Pinot Grigio',
        'parent' => 1,  // categoryid for 'white'.
        'details' => 'From the northeast of Italy',
        'attributes' => [
          'fruity',
          'medium-bodied',
          'slightly sweet',
        ],
      ];
    }

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
