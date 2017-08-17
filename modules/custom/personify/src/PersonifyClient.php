<?php

namespace Drupal\personify;

use Drupal\openy_campaign\CRMClientInterface;
use Drupal\Core\Site\Settings;

/**
 * Helper for Personify API requests needed for retention campaign.
 */
class PersonifyClient implements CRMClientInterface {

  protected $endpoint;
  protected $username;
  protected $password;

  public function __construct() {
    $this->endpoint = Settings::get('personify_endpoint');
    $this->username = Settings::get('personify_username');
    $this->password = Settings::get('personify_password');

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

      \Drupal::logger('personify')->info('Personify request to %method. Arguments %json. Response %body', [
        '%method' => $method,
        '%json' => json_encode($json),
        '%body' => $body,
      ]);

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
  public function getMemberInformation($facility_id) {
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
  public function getVisitCountByDate($master_id, \DateTime $date_from, \DateTime $date_to) {
    $json = [
      'CL_GetFacilityVisitCountByDateInput' => [
        'MasterCustomerId' => $master_id,
        'DateFrom' => $date_from->format('Y-m-d H:i:s'),
        'DateTo' => $date_to->format('Y-m-d H:i:s'),
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
  public function getVisitsBatch(array $list_ids, \DateTime $date_from, \DateTime $date_to) {
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
