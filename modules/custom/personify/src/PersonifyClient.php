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
      $content = $body->getContents();

      \Drupal::logger('personify')->info('Personify request to %method. Arguments %json. Response %body', [
        '%method' => $method,
        '%json' => json_encode($json),
        '%body' => $content,
      ]);

      return json_decode($content);
    }
    catch (\Exception $e) {
      watchdog_exception('personify', $e);
    }
    return [];
  }

  /**
   * Get information about member by its facility access ID.
   *
   * @param int $facilityId
   *   Facility Access ID.
   *
   * @return array
   *   Information about Member.
   */
  public function getMemberInformation($facilityId) {
    $json = [
      'CL_GetCustomerBranchInformationInput' => [
        'CardNumber' => $facilityId,
      ],
    ];

    return $this->doAPIcall('CL_GetCustomerBranchInformation', $json);
  }

  /**
   * Get information about member visits for a period.
   *
   * @param int $masterId
   *   Master Customer ID.
   * @param \DateTime $dateFrom
   *   Date From.
   * @param \DateTime $dateTo
   *   Date To.
   *
   * @return array|\stdClass
   *   Information about Member visits for a period.
   */
  public function getVisitCountByDate($masterId, \DateTime $dateFrom, \DateTime $dateTo) {
    $json = [
      'CL_GetFacilityVisitCountByDateInput' => [
        'MasterCustomerId' => $masterId,
        'DateFrom' => $dateFrom->format('Y-m-d H:i:s'),
        'DateTo' => $dateTo->format('Y-m-d H:i:s'),
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
   * @param array $listIds
   *   Array with list of master customer ids.
   * @param \DateTime $dateFrom
   *   Date From.
   * @param \DateTime $dateTo
   *   Date To.
   *
   * @return array|\stdClass
   *   Information about Members visits for a period.
   */
  public function getVisitsBatch(array $listIds, \DateTime $dateFrom, \DateTime $dateTo) {
    $json = [
      'CL_GetFacilityVisitCountByDateInput' => [
        'MasterCustomerId' => implode(',', $listIds),
        'DateFrom' => $dateFrom->format('Y-m-d H:i:s'),
        'DateTo' => $dateTo->format('Y-m-d H:i:s'),
      ],
    ];

    return $this->doAPIcall('CL_GetFacilityVisitCountByDate', $json);
  }

}
