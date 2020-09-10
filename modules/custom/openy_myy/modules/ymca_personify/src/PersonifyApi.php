<?php

namespace Drupal\ymca_personify;

use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use GuzzleHttp\Client;

/**
 * Helper for Personify API requests needed for retention campaign.
 */
class PersonifyApi implements PersonifyApiInterface {

  /**
   * Personify date format.
   */
  const PERSONIFY_DATE_FORMAT = 'Y-m-d\TH:i:s';

  /**
   * Http client.
   *
   * @var Client
   */
  protected $client;

  /**
   * Config factory.
   *
   * @var ConfigFactory
   */
  protected $config;

  /**
   * The logger channel.
   *
   * @var LoggerChannelInterface
   */
  protected $logger;

  /**
   * Endpoint settings.
   *
   * @var array
   */
  protected $endpointSettings = [];

  /**
   * Creates a new PersonifyApi service.
   *
   * @param Client $client
   *   Http client.
   * @param ConfigFactory $config
   *   Config factory.
   * @param LoggerChannelFactoryInterface $logger_factory
   *   The logger channel.
   */
  public function __construct(Client $client, ConfigFactory $config, LoggerChannelFactoryInterface $logger_factory) {
    $this->client = $client;
    $this->config = $config;
    $this->logger = $logger_factory->get('ymca_personify');
    $this->endpointSettings = $this->getConfig();
  }

  /**
   * Get config.
   *
   * @return array
   *   Config params.
   */
  protected function getConfig() {
    $config = $this->config->get('ymca_personify.api')->getRawData();
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
   * Build URL to Personify API method.
   *
   * @param string $api
   *   API name.
   *
   * @return string
   *   URL.
   */
  public function buildApiUrl($api) {
    return $this->endpointSettings['endpoint'] . $api;
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
        $this->endpointSettings['username'],
        $this->endpointSettings['password'],
      ],
    ];
    try {
      $url = $this->buildApiUrl('CL_GetCustomerBranchInformation');
      $response = $this->client->request('POST', $url, $options);

      if ($response->getStatusCode() != '200') {
        throw new \LogicException(t('API Method GetCustomerBranchInformation is failed.'));
      }
      $body = $response->getBody();

      return json_decode($body->getContents());
    }
    catch (\Exception $e) {
      $this->logger->error('Failed to get Personify data: %msg', [
        '%msg' => $e->getMessage(),
      ]);
    }

    return [];
  }

  /**
   * Get product listing filtered by some parameters.
   *
   * @param string $product_class_code
   *   Product Class code.
   * @param \DateTime $available_date
   *   Available date.
   * @param \DateTime $expiration_date
   *   Expiration date.
   * @param string|null $branch
   *   Branch ID in Personify. One id or multiple split by coma.
   *
   * @return array
   *   API response.
   */
  public function getProductListing($product_class_code, \DateTime $available_date, \DateTime $expiration_date, $branch = NULL) {
    $options = [
      'json' => [
        'CL_GetProductListingInput' => [
          'ProductClassCode' => $product_class_code,
          'ProductStatusCode' => 'A',
          'AvailableDate' => $available_date->format(\Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface::DATETIME_STORAGE_FORMAT),
          'ExpirationDate' => $expiration_date->format(\Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface::DATETIME_STORAGE_FORMAT),
          'AvailableToOrdersFlag' => TRUE,
        ],
      ],
      'headers' => [
        'Content-Type' => 'application/json;charset=utf-8',
      ],
      'auth' => [
        $this->endpointSettings['username'],
        $this->endpointSettings['password'],
      ],
    ];
    // If branch is not empty then add filter by branch.
    if (!empty($branch)) {
      $options['json']['CL_GetProductListingInput']['BranchCodes'] = $branch;
    }

    try {
      $url = $this->buildApiUrl('CL_GetProductListing');
      $response = $this->client->request('POST', $url, $options);

      if ($response->getStatusCode() != '200') {
        throw new \LogicException(t('API Method CL_GetProductListing is failed.'));
      }
      $body = $response->getBody();
      return json_decode($body->getContents());
    }
    catch (\Exception $e) {
      $this->logger->error('Failed to get Personify data: %msg', [
        '%msg' => $e->getMessage(),
      ]);
    }

    return [];
  }

}
