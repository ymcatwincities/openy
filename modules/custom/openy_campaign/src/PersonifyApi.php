<?php

namespace Drupal\openy_campaign;

// @todo Refactoring!!! Should be merged with ymca_personify module.
/**
 * Helper for Personify API requests needed for retention campaign.
 */
class PersonifyApi {

  /**
   * Get config.
   *
   * @return array
   *   Config params.
   */
  public function getConfig() {
    $config = \Drupal::config('ymca_retention.api')->getRawData();
    switch ($config['environment']) {
      case 'prod':
        $config['endpoint'] = $config['prod_endpoint'];
        $config['username'] = $config['prod_username'];
        $config['password'] = $config['prod_password'];
        break;

      case 'stage':
        $config['endpoint'] = $config['stage_endpoint'];
        $config['username'] = $config['stage_username'];
        $config['password'] = $config['stage_password'];
        break;
    }

    return $config;
  }

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
    $config = self::getConfig();
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
   * @param int $master_id
   *   Master Customer ID.
   * @param int $date_from
   *   Date From.
   * @param int $date_to
   *   Date To.
   *
   * @return array|\stdClass
   *   Information about Member visits for a period.
   */
  public static function getPersonifyVisitCountByDate($master_id, $date_from, $date_to) {
    $config = self::getConfig();
    $client = \Drupal::httpClient();
    $options = [
      'json' => [
        'CL_GetFacilityVisitCountByDateInput' => [
          'MasterCustomerId' => $master_id,
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
        throw new \LogicException(t('API Method CL_GetFacilityVisitCountByDate is failed.'));
      }
      $body = $response->getBody();
      $results = json_decode($body->getContents());
      if (!empty($results->ErrorMessage)) {
        $logger = \Drupal::logger('openy_campaign_queue');
        $logger->alert('Could not retrieve visits information for members for batch operation');
        return [];
      }
      $visits = $results->FacilityVisitCustomerRecord;
      return reset($visits);
    }
    catch (\Exception $e) {
      watchdog_exception('ymca_personify', $e);
    }
    return [];
  }

  /**
   * Get information about member visits for a period.
   *
   * @param array $list_ids
   *   Array with list of master customer ids.
   * @param \DateTime $date_from
   *   Date From.
   * @param \DateTime $date_to
   *   Date To.
   *
   * @return array|\stdClass
   *   Information about Members visits for a period.
   */
  public static function getPersonifyVisitsBatch(array $list_ids, \DateTime $date_from, \DateTime $date_to) {
    $config = self::getConfig();
    $client = \Drupal::httpClient();
    $options = [
      'json' => [
        'CL_GetFacilityVisitCountByDateInput' => [
          'MasterCustomerId' => implode(',', $list_ids),
          'DateFrom' => $date_from->format('Y-m-d H:i:s'),
          'DateTo' => $date_to->format('Y-m-d H:i:s'),
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
        throw new \LogicException(t('API Method CL_GetFacilityVisitCountByDate is failed.'));
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
