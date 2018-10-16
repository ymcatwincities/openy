<?php

namespace Drupal\ygtc_pef_gxp_sync\syncer;

use Drupal\Core\Logger\LoggerChannel;
use Drupal\ymca_mappings\LocationMappingRepository;
use GuzzleHttp\ClientInterface as HttpClientInterface;

/**
 * Class Fetcher.
 *
 * @package Drupal\ygtc_pef_gxp_sync\syncer
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
   * @var \Drupal\ygtc_pef_gxp_sync\syncer\WrapperInterface
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
   * Fetcher constructor.
   *
   * @param \Drupal\ygtc_pef_gxp_sync\syncer\WrapperInterface $wrapper
   *   Wrapper.
   * @param \Drupal\Core\Logger\LoggerChannel $loggerChannel
   *   Logger.
   * @param \GuzzleHttp\ClientInterface
   *   Http client.
   * @param \Drupal\ymca_mappings\LocationMappingRepository $mappingRepository
   *   Location mapping repo.
   */
  public function __construct(WrapperInterface $wrapper, LoggerChannel $loggerChannel, HttpClientInterface $client, LocationMappingRepository $mappingRepository) {
    $this->wrapper = $wrapper;
    $this->logger = $loggerChannel;
    $this->client = $client;
    $this->mappingRepository = $mappingRepository;
  }

  /**
   * {@inheritdoc}
   */
  public function fetch() {
    $apiPrefix = self::API_URL . self::CLIENT_ID . '/';

    $locations = $this->mappingRepository->loadAllLocationsWithGroupExId();

    // @todo Get only one location for debugging.
    $locations = array_slice($locations, 0, 1);

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
