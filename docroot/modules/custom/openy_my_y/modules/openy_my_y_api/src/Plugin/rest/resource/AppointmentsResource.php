<?php

namespace Drupal\openy_my_y_api\Plugin\rest\resource;

use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ModifiedResourceResponse;

/**
 * Provides Appointments Resource.
 *
 * @RestResource(
 *   id = "openy_my_y_api_appointments",
 *   label = @Translation("Appointments"),
 *   uri_paths = {
 *     "canonical" = "/openy_my_y/appointments/{uid}"
 *   }
 * )
 */
class AppointmentsResource extends ResourceBase {

  /**
   * Responds to entity GET requests.
   * @return \Drupal\rest\ModifiedResourceResponse
   */
  public function get($uid = NULL) {
    /** @var \Drupal\mindbody_cache_proxy\MindbodyCacheProxy $client */
    $client = \Drupal::service('mindbody_cache_proxy.client');
    $credentials = \Drupal::configFactory()->get('mindbody.settings');

    $request = [
      'UserCredentials' => [
        'Username' => $credentials->get('user_name'),
        'Password' => $credentials->get('user_password'),
        'SiteIDs' => [$credentials->get('site_id')],
      ],
      'ClientID' => $uid,
      'StartDate' => date('Y-m-d', strtotime('today')),
      'EndDate' => date('Y-m-d', strtotime("today +20 weeks")),
    ];

    $allow_origin = 'http://0.0.0.0:8080';
    $allowed_origins = [
      'http://account.ymcamn.org',
      'http://0.0.0.0:8080',
      'https://account.ymcamn.org',
      'https://www.ymcamn.org',
    ];
    if (in_array($_SERVER['HTTP_ORIGIN'], $allowed_origins)) {
      $allow_origin = $_SERVER['HTTP_ORIGIN'];
    }

    $results = [];

    try {
      $result = $client->call(
        'ClientService',
        'GetClientSchedule',
        $request,
        TRUE
      );
    } catch (\Exception $e) {
      return new ModifiedResourceResponse(
        [
          'status' => FALSE,
          'message' => $e->getMessage(),
          'timestamp' => \Drupal::time()->getRequestTime(),
          'results' => [],
        ]
      );
    }

    $visits = $result->GetClientScheduleResult->Visits;

    // In case if there is no appointments at all.
    if (!isset($visits->Visit)) {
      $response = new ModifiedResourceResponse(
        [
          'status' => TRUE,
          'message' => '',
          'timestamp' => \Drupal::time()->getRequestTime(),
          'results' => [],
        ]
      );

      $response->headers->add(
        [
          'Access-Control-Allow-Origin' => $allow_origin,
          'Access-Control-Allow-Methods' => "POST, GET, OPTIONS, PATCH, DELETE",
          'Access-Control-Allow-Headers' => "Authorization, X-CSRF-Token, Content-Type",
        ]
      );

      return $response;
    }

    // There is only one appointment.
    if (is_object($visits->Visit)) {
      $results[] = $visits->Visit;
    }
    else {
      // Get all available results.
      $results = $visits->Visit;
    }

    $data = [];
    foreach ($results as $appointment) {
      $data[] = [
        'id' => $appointment->AppointmentID,
        'start' => $appointment->StartDateTime,
        'end' => $appointment->EndDateTime,
        'session' => $appointment->Name,
        'trainer' => [
          'id' => $appointment->Staff->ID,
          'name' => $appointment->Staff->Name,
        ],
        'location' => [
          'id' => $appointment->Location->ID,
          'name' => $appointment->Location->Name,
        ],
      ];
    }

    $response = new ModifiedResourceResponse(
      [
        'status' => TRUE,
        'message' => '',
        'timestamp' => \Drupal::time()->getRequestTime(),
        'results' => $data,
      ]
    );

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
