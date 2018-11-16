<?php

namespace Drupal\openy_my_y_api\Plugin\rest\resource;

use Drupal\rest\ModifiedResourceResponse;
use Drupal\rest\Plugin\ResourceBase;

/**
 * Class MyYResourceBase.
 *
 * @package Drupal\openy_my_y_api\Plugin\rest\resource
 */
abstract class MyYResourceBase extends ResourceBase {

  /**
   * Prepare response object with valid headers.
   *
   * @param array $data
   *   Contents of the response.
   *
   * @return \Drupal\rest\ModifiedResourceResponse
   *   Response.
   */
  protected function prepareResponse(array $data) {
    $allow_origin = 'http://0.0.0.0:8080';
    $allowed_origins = [
      'http://***REMOVED***',
      'http://0.0.0.0:8080',
      'https://***REMOVED***',
      'https://www.ymcamn.org',
    ];
    if (in_array($_SERVER['HTTP_ORIGIN'], $allowed_origins)) {
      $allow_origin = $_SERVER['HTTP_ORIGIN'];
    }

    // Set default response data.
    $defaults['timestamp'] = \Drupal::time()->getRequestTime();
    $defaults['message'] = '';
    $defaults['results'] = [];

    $data = array_merge($defaults, $data);
    $response = new ModifiedResourceResponse($data);

    $response->headers->add(
      [
        'Access-Control-Allow-Origin' => $allow_origin,
        'Access-Control-Allow-Methods' => "POST, GET, OPTIONS, PATCH, DELETE",
        'Access-Control-Allow-Headers' => "Authorization, X-CSRF-Token, Content-Type",
      ]
    );

    return $response;
  }

}
