<?php

namespace Drupal\openy_pef_gxp_sync\syncer;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LoggerChannel;
use Drupal\openy_mappings\LocationMappingRepository;
use Drupal\openy_pef_gxp_sync\OpenYPefGxpSyncException;
use GuzzleHttp\ClientInterface as HttpClientInterface;

/**
 * Class Fetcher.
 *
 * @todo Suppress `Genera` category here.
 *
 * @package Drupal\openy_pef_gxp_sync\syncer
 */
class Fetcher implements FetcherInterface {

  /**
   * API URL.
   */
  const API_URL = 'https://www.groupexpro.com/gxp/api/openy/view/';

  /**
   * Set 1 to set debug mode.
   */
  const DEBUG_MODE = 0;

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
   * Config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Cache backend.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cacheBackend;

  /**
   * Fetcher constructor.
   *
   * @param \Drupal\openy_pef_gxp_sync\syncer\WrapperInterface $wrapper
   *   Wrapper.
   * @param \Drupal\Core\Logger\LoggerChannel $loggerChannel
   *   Logger.
   * @param \GuzzleHttp\ClientInterface $client
   *   Http client.
   * @param \Drupal\openy_mappings\LocationMappingRepository $mappingRepository
   *   Location mapping repo.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   Config factory.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cacheBackend
   *   Cache backend.
   */
  public function __construct(WrapperInterface $wrapper, LoggerChannel $loggerChannel, HttpClientInterface $client, LocationMappingRepository $mappingRepository, ConfigFactoryInterface $configFactory, CacheBackendInterface $cacheBackend) {
    $this->wrapper = $wrapper;
    $this->logger = $loggerChannel;
    $this->client = $client;
    $this->mappingRepository = $mappingRepository;
    $this->configFactory = $configFactory;
    $this->cacheBackend = $cacheBackend;
  }

  /**
   * {@inheritdoc}
   */
  public function fetch() {
    $this->logger->info('%name started.', ['%name' => get_class($this)]);

    $openyGxpConfig = $this->configFactory->get('openy_gxp.settings');
    $clientId = $openyGxpConfig->get('client_id');
    if (!$clientId) {
      $this->logger->error('No GroupEx client ID found. Please, configure OpenY GXP module and provide Client ID.');
      return;
    }

    $apiPrefix = self::API_URL . $clientId . '/';

    $locations = $this->mappingRepository->loadAllLocationsWithGroupExId();

    foreach ($locations as $location) {
      $locationGpxId = $location->field_groupex_id->value;
      $locationId = $location->field_location_ref->target_id;

      if (self::DEBUG_MODE && $cachedData = $this->cacheBackend->get(get_class($this) . '_new_' . $locationId)) {
        $this->wrapper->setSourceData($locationId, $cachedData->data);
        continue;
      }

      try {
        $this->logger->debug('Started fetching data for locationId %id', ['%id' => $locationId]);
        $request = $this->client->request('GET', $apiPrefix . '/' . $locationGpxId);
      }
      catch (\Exception $exception) {
        throw new OpenYPefGxpSyncException(sprintf('Failed to get schedules for location with ID: %d', $locationId));
      }

      $response = json_decode((string) $request->getBody(), TRUE);

      $this->wrapper->setSourceData($locationId, $response);

      if (self::DEBUG_MODE) {
        $this->cacheBackend->set(get_class($this) . '_new_' . $locationId, $response);
      }
    }

    $this->logger->info('%name finished.', ['%name' => get_class($this)]);
  }

}
