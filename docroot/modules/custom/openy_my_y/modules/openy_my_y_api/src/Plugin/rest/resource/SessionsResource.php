<?php

namespace Drupal\openy_my_y_api\Plugin\rest\resource;

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
class SessionsResource extends MyYResourceBase {

  /**
   * Responds to entity GET requests.
   *
   * @return \Drupal\rest\ModifiedResourceResponse
   *   Response.
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
      'ShowActiveOnly' => TRUE,
      // @todo As per MB UI - ClassID is 0 by default
      'ClassID' => 0,
      // @todo Check if we need other program IDs to be included.
      'ProgramIDs' => [2],
    ];

    $results = [];

    try {
      $result = $client->call(
        'ClientService',
        'GetClientServices',
        $request,
        TRUE
      );
    }
    catch (\Exception $e) {
      return $this->prepareResponse([
        'status' => FALSE,
        'message' => $e->getMessage(),
      ]);
    }

    $sessions = $result->GetClientServicesResult->ClientServices;

    // In case if there is no appointments at all.
    if (!isset($sessions->ClientService)) {
      return $this->prepareResponse([
        'status' => TRUE,
      ]);
    }

    // There is only one session.
    if (is_object($sessions->ClientService)) {
      $results[] = $sessions->ClientService;
    }
    else {
      // Get all available sessions.
      $results = $sessions->ClientService;
    }

    $data = [];
    foreach ($results as $session) {
      $data[] = [
        'id' => $session->ID,
        'session' => $session->Name,
        'remaining' => "$session->Remaining/$session->Count",
      ];
    }

    return $this->prepareResponse([
      'status' => TRUE,
      'results' => $data,
    ]);
  }

}
