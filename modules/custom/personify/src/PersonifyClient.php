<?php

namespace Drupal\personify;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Site\Settings;
use GuzzleHttp\Client;

/**
 * Helper for Personify API requests needed for retention campaign.
 */
class PersonifyClient {

  /** @var string Personify API endpoint */
  protected $endpoint;

  /** @var string Personify API username */
  protected $username;

  /** @var string Personify API password */
  protected $password;

  /**
   * @var \GuzzleHttp\Client
   */
  protected $client;

  /**
   * @var array
   */
  protected $config;

  /**
   * PersonifyClient constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   * @param \GuzzleHttp\Client $client
   */
  public function __construct(ConfigFactoryInterface $configFactory, Client $client) {
    $this->client = $client;
    $this->config = $configFactory->get('personify.settings');
    $env = $this->config->get('environment');

    $this->endpoint = $this->config->get($env . '_endpoint');
    $this->username = $this->config->get($env . '_username');
    $this->password = $this->config->get($env . '_password');

    if (empty($this->endpoint) || empty($this->username) || empty($this->password)) {
      throw new \LogicException(t('Personify module is misconfigured. Make sure endpoint, username and password details are set.'));
    }
  }

  /**
   * @param string $type
   * @param string $method
   * @param array $body
   * @param string $body_format
   *
   * @return array|mixed
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  public function doAPIcall($type = 'GET', $method = '', $body = [], $body_format = 'json') {

    $body_key = 'body';
    if ($body_format == 'json') {
      $body_key = 'json';
    }

    $options = [
      $body_key => $body,
      'headers' => [
        'Content-Type' => 'application/' . $body_format . ';charset=utf-8',
      ],
      'auth' => [
        $this->username,
        $this->password,
      ],
    ];

    try {

      $endpoint = $this->endpoint . $method;

      $response = $this->client->request($type, $endpoint, $options);

      if ($response->getStatusCode() != '200') {
        throw new \LogicException(t('API Method %method is failed.', ['%method' => $method]));
      }

      $content = $response->getBody()->getContents();
      \Drupal::logger('personify')->info('Personify request to %method. Arguments %json. Response %body', [
        '%method' => $method,
        '%json' => json_encode($body),
        '%body' => $content,
      ]);

      return json_decode($content, TRUE);
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

    return $this->doAPIcall('POST', 'CL_GetCustomerBranchInformation', $json);
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
