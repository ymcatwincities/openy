<?php

namespace Drupal\ymca_retention;

// @todo This is a temporary class, which should be modified, when branch "yptf-mvp-2" will be merged.
/**
 * Helper for Personify API requests needed for retention campaign.
 */
class PersonifyApi {

  /**
   * Get information about member by its facility access ID.
   *
   * @param int $facility_id
   *   Facility Access ID.
   *
   * @return array
   *   Information about Member.
   */
  public static function getPersonifyMemberInformation($facility_id) {
    $config = \Drupal::config('ymca_retention.api')->getRawData();
    $client = \Drupal::httpClient();
    $options = [
      'json' => [
        'CL_GetCustomerBranchInformationInput' => [
          'CardNumber' => $facility_id,
        ],
      ],
      'headers' => [
        'Content-Type' => 'application/json;charset=utf-8',
      ],
      'auth' => [
        $config['username'],
        $config['password'],
      ],
    ];
    try {
      $endpoint = $config['endpoint'] . 'CL_GetCustomerBranchInformation';
      $response = $client->request('POST', $endpoint, $options);
      if ($response->getStatusCode() != '200') {
        throw new \LogicException(t('API Method GetCustomerBranchInformation is failed.'));
      }
      $body = $response->getBody();
      return json_decode($body->getContents());
    }
    catch (\Exception $e) {
      watchdog_exception('ymca_personify', $e);
    }
    return [];
  }

  /**
   * Get information about member visits for a period.
   *
   * @param int $facility_id
   *   Facility Access ID.
   * @param int $date_from
   *   Date From.
   * @param int $date_to
   *   Date To.
   *
   * @return array|\stdClass
   *   Information about Member visits for a period.
   */
  public static function getPersonifyVisitCountByDate($facility_id, $date_from, $date_to) {
    $config = \Drupal::config('ymca_retention.api')->getRawData();
    $client = \Drupal::httpClient();
    $options = [
      'json' => [
        'CL_GetFacilityVisitCountByDateInput' => [
          'CardNumber' => $facility_id,
          'DateFrom' => $date_from,
          'DateTo' => $date_to,
        ],
      ],
      'headers' => [
        'Content-Type' => 'application/json;charset=utf-8',
      ],
      'auth' => [
        $config['username'],
        $config['password'],
      ],
    ];
    try {
      $endpoint = $config['endpoint'] . 'CL_GetFacilityVisitCountByDate';
      $response = $client->request('POST', $endpoint, $options);
      if ($response->getStatusCode() != '200') {
        throw new \LogicException(t('API Method CL_GetFacilityVisitCountByDateInput is failed.'));
      }
      $body = $response->getBody();
      return json_decode($body->getContents());
    }
    catch (\Exception $e) {
      watchdog_exception('ymca_personify', $e);
    }
    return [];
  }

}
