<?php

namespace Drupal\openy_pef_gxp_sync\syncer;

use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Logger\LoggerChannel;
use Drupal\openy_mappings\LocationMappingRepository;
use GuzzleHttp\ClientInterface as HttpClientInterface;

/**
 * Class Fetcher.
 *
 * @package Drupal\openy_pef_gxp_sync\syncer
 */
class Fetcher implements FetcherInterface {

  /**
   * YGTC Client ID.
   */
  const CLIENT_ID = 3;

  /**
   * API URL.
   */
  const API_URL = 'https://www.groupexpro.com/gxp/api/openy/view/';

  /**
   * Wrapper.
   *
   * @var \Drupal\openy_pef_gxp_sync\syncer\WrapperInterface
   */
  protected $wrapper;

  /**
   * Logger channel.
   *
   * @var \Drupal\Core\Logger\LoggerChannel
   */
  protected $logger;

  /**
   * Http client.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $client;

  /**
   * Mapping repository.
   *
   * @var \Drupal\ymca_mappings\LocationMappingRepository
   */
  protected $mappingRepository;

  /**
   * Config.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * Fetcher constructor.
   *
   * @param \Drupal\openy_pef_gxp_sync\syncer\WrapperInterface $wrapper
   *   Wrapper.
   * @param \Drupal\Core\Logger\LoggerChannel $loggerChannel
   *   Logger.
   * @param \GuzzleHttp\ClientInterface $client
   *   Http client.
   * @param \Drupal\ymca_mappings\LocationMappingRepository $mappingRepository
   *   Location mapping repo.
   * @param \Drupal\Core\Config\ImmutableConfig $config
   *   Config.
   */
  public function __construct(WrapperInterface $wrapper, LoggerChannel $loggerChannel, HttpClientInterface $client, LocationMappingRepository $mappingRepository, ImmutableConfig $config) {
    $this->wrapper = $wrapper;
    $this->logger = $loggerChannel;
    $this->client = $client;
    $this->mappingRepository = $mappingRepository;
    $this->config = $config;
  }

  /**
   * {@inheritdoc}
   */
  public function fetch() {
    $config = \Drupal::configFactory()->get('openy_gxp.settings');
    $clientId = $config->get('client_id');
    if (!$clientId) {
      $this->logger->error('No GroupEx client ID found. Please, configure OpenY GXP module and provide Client ID.');
      return;
    }

    $apiPrefix = self::API_URL . $clientId . '/';

    $locations = $this->mappingRepository->loadAllLocationsWithGroupExId();

    foreach ($locations as $location) {
      $locationGpxId = $location->field_groupex_id->value;
      $locationId = $location->field_location_ref->target_id;

      try {
        $request = $this->client->request('GET', $apiPrefix . '/' . $locationGpxId);
      }
      catch (\Exception $exception) {
        $this->logger->error('Failed to get schedules for location %location', ['%location' => $locationId]);
      }

      $response = json_decode((string) $request->getBody(), TRUE);
      $this->wrapper->setSourceData($locationId, $response);
    }
  }

}
