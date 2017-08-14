<?php

namespace Drupal\personify;

/**
 * Helper for Personify API requests needed for retention campaign.
 */
class PersonifyClient {

  protected $endpoint;
  protected $username;
  protected $password;

  public function __construct() {
    $this->endpoint = \Drupal\Core\Site\Settings::get('personify_endpoint');
    $this->username = \Drupal\Core\Site\Settings::get('personify_username');
    $this->password = \Drupal\Core\Site\Settings::get('personify_password');

    if (empty($this->endpoint) || empty($this->username) || empty($this->password)) {
      throw new \LogicException(t('Personify module is misconfigured. Make sure endpoint, username and password details are set.'));
    }
  }

  protected function doAPIcall($method, $json) {
    $client = \Drupal::httpClient();
    $options = [
      'json' => $json,
      'headers' => [
        'Content-Type' => 'application/json;charset=utf-8',
      ],
      'auth' => [
        $this->username,
        $this->password,
      ],
    ];
    try {
      $endpoint = $this->endpoint . $method;
      $response = $client->request('POST', $endpoint, $options);
      if ($response->getStatusCode() != '200') {
        throw new \LogicException(t('API Method %method is failed.', array('%method' => $method)));
      }
      $body = $response->getBody();
      return json_decode($body->getContents());
    }
    catch (\Exception $e) {
      watchdog_exception('personify', $e);
    }
    return [];
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
  public function getPersonifyMemberInformation($facility_id) {
    $json = [
      'CL_GetCustomerBranchInformationInput' => [
        'CardNumber' => $facility_id,
      ],
    ];

    return $this->doAPIcall('CL_GetCustomerBranchInformation', $json);
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
  public function getPersonifyVisitCountByDate($master_id, $date_from, $date_to) {
    $json = [
      'CL_GetFacilityVisitCountByDateInput' => [
        'MasterCustomerId' => $master_id,
        'DateFrom' => $date_from,
        'DateTo' => $date_to,
      ],
    ];

    $results = $this->doAPIcall('CL_GetFacilityVisitCountByDate', $json);
    if (empty($results)) {
      return [];
    }

    if (!empty($results->ErrorMessage)) {
      $logger = \Drupal::logger('openy_campaign_queue');
      $logger->alert('Could not retrieve visits information for members for batch operation');
      return [];
    }
    $visits = $results->FacilityVisitCustomerRecord;
    return reset($visits);
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
  public function getPersonifyVisitsBatch(array $list_ids, \DateTime $date_from, \DateTime $date_to) {
    $json = [
      'CL_GetFacilityVisitCountByDateInput' => [
        'MasterCustomerId' => implode(',', $list_ids),
        'DateFrom' => $date_from->format('Y-m-d H:i:s'),
        'DateTo' => $date_to->format('Y-m-d H:i:s'),
      ],
    ];

    return $this->doAPIcall('CL_GetFacilityVisitCountByDate', $json);
  }

}
