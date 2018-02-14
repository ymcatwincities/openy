<?php

namespace Drupal\openy_my_y_api\Plugin\rest\resource;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;

/**
 * Provides Sessions Resource.
 *
 * @RestResource(
 *   id = "openy_my_y_api_sessions",
 *   label = @Translation("Sessions"),
 *   uri_paths = {
 *     "canonical" = "/openy_my_y/sessions/{uid}"
 *   }
 * )
 */
class SessionsResource extends ResourceBase {

  /**
   * Responds to entity GET requests.
   * @return \Drupal\rest\ResourceResponse
   */
  public function get($uid = NULL) {
    $data = [
      [
        'id' => 123,
        'session' => 'PT Full 60 min – 4 (MEMBER)',
        'remaining' => '4/8',
      ],
      [
        'id' => 124,
        'session' => 'PT Express 30 min – 8 (MEMBER)',
        'remaining' => '4/5',
      ],
    ];

    $response = new ResourceResponse([
      'status' => TRUE,
      'message' => '',
      'timestamp' => \Drupal::time()->getRequestTime(),
      'results' => $data,
    ]);

    $response->headers->add(
      [
        'Access-Control-Allow-Origin' => 'http://0.0.0.0:8080',
        'Access-Control-Allow-Methods' => "POST, GET, OPTIONS, PATCH, DELETE",
        'Access-Control-Allow-Headers' => "Authorization, X-CSRF-Token, Content-Type",
      ]
    );
    return $response;
  }

}
