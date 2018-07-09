<?php

namespace Drupal\openy_prgreg_api\Plugin\rest\resource;

use Drupal\Component\Serialization\Json;
use Drupal\rest\ModifiedResourceResponse;
use Drupal\rest\Plugin\ResourceBase;

/**
 * Provides Locations mock-up.
 *
 * @RestResource(
 *   id = "openy_prgreg_api_locations",
 *   label = @Translation("Locations"),
 *   label = @Translation("Program Registration: Locations"),
 *   uri_paths = {
 *     "canonical" = "/prgreg/locations"
 *   }
 * )
 */
class Locations extends ResourceBase {

  /**
   * {@inheritdoc}
   */
  public function get() {
    $path = drupal_get_path('module', 'openy_prgreg_api');
    $results = Json::decode(file_get_contents($path . '/data/locations.json'));
    $data = ['results' => $results];

    $defaults['timestamp'] = \Drupal::time()->getRequestTime();
    $defaults['status'] = true;
    $defaults['message'] = "OK";

    $data = array_merge($defaults, $data);
    $response = new ModifiedResourceResponse($data);

    return $response;
  }

}
