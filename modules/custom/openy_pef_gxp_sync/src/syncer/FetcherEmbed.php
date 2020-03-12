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
 * @package Drupal\openy_pef_gxp_sync\syncer
 */
class FetcherEmbed implements FetcherInterface {

  /**
   * API URL.
   */
  const API_URL = 'http://api.groupexpro.com/schedule/embed';

  /**
   * Timezone of the API results.
   */
  const API_TIMEZONE = 'America/Chicago';

  /**
   * Date format for API.
   */
  const API_DATE_FORMAT = 'l, F j, Y';

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

    try {
      $date = new \DateTime('now', new \DateTimeZone(self::API_TIMEZONE));
    }
    catch (\Exception $exception) {
      $msg = 'Failed to instantiate date object with message %s';
      throw new OpenYPefGxpSyncException(sprintf($msg, $exception->getMessage()));
    }

    $start = $date->format('U');
    $date->add(new \DateInterval('P1M'));
    $end = $date->format('U');

    $locations = $this->mappingRepository->loadAllLocationsWithGroupExId();

    $requestOptions = [
      'query' => [
        'a' => $clientId,
        'schedule' => TRUE,
        'category' => 'General',
        'start' => $start,
        'end' => $end,
        'description' => TRUE,
      ],
    ];

    foreach ($locations as $location) {
      $locationGpxId = $location->field_groupex_id->value;
      $locationId = $location->field_location_ref->target_id;

      $embedSourceData = [];
      $requestOptions['query']['location'] = $locationGpxId;

      $data = $this->getCachedData($locationId);
      if (is_null($data)) {
        try {
          $this->logger->debug('Started fetching data for locationId %id', ['%id' => $locationId]);
          $response = $this->client->request('GET', self::API_URL, $requestOptions);
        }
        catch (\Exception $exception) {
          $msg = 'Failed to make request to API with message: %s';
          throw new OpenYPefGxpSyncException(sprintf($msg, $exception->getMessage()));
        }

        $body = $response->getBody();
        $data = json_decode($body->getContents());

        $this->setCachedData($locationId, $data);
      }

      foreach ($data as $dataItem) {
        try {
          $classItem = $this->createClassItem((array) $dataItem);
          $embedSourceData[] = $classItem;
        }
        catch (\Exception $exception) {
          $msg = 'Failed to create classItem from data for class_id %s';
          throw new OpenYPefGxpSyncException(sprintf($msg, $dataItem['id']));
        }
      }

      $this->wrapper->setSourceData($locationId, $embedSourceData);
    }

    $this->logger->info('%name finished.', ['%name' => get_class($this)]);
  }

  /**
   * Get cached data.
   *
   * @param int $locationId
   *   Location ID.
   *
   * @return array|null
   *   Cached data.
   */
  private function getCachedData($locationId) {
    if (!self::DEBUG_MODE) {
      return NULL;
    }

    $cachedData = $this->cacheBackend->get(get_class($this) . '_embed_' . $locationId);
    if ($cachedData) {
      return $cachedData->data;
    }

    return NULL;
  }

  /**
   * Set cached data.
   *
   * @param int $locationId
   *   Location ID.
   * @param array $data
   *   Data to set.
   */
  private function setCachedData($locationId, array $data) {
    if (self::DEBUG_MODE) {
      $this->cacheBackend->set(get_class($this) . '_embed_' . $locationId, $data);
    }
  }

  /**
   * Creates class item from provided data.
   *
   * @param array $data
   *   Class item data from embed API.
   *
   * @return array
   *   Class item compatible with syncer.
   *
   * @throws \Exception
   */
  private function createClassItem(array $data) {
    $classItem = [
      'class_id' => trim($data['id']),
      'category' => trim($data['category']),
      'location' => trim($data['location']),
      'title' => trim($data['title']),
      'description' => trim($this->getDescription($data)),
      'start_date' => $this->getStartDate($data),
      'end_date' => $this->getEndDate($data),
      'recurring' => 'weekly',
      'studio' => trim($data['studio']),
      'instructor' => trim(strip_tags($data['instructor'])),
      'patterns' => [
          'day' => $this->getDay($data),
          'start_time' => $this->getStartTime($data),
          'end_time' => $this->getEndTime($data),
      ],
    ];

    return $classItem;
  }

  /**
   * Get patterns day.
   *
   * @param array $data
   *   Class data.
   *
   * @return string
   *   Day.
   *
   * @throws \Exception
   */
  private function getDay(array $data) {
    $dates = $this->getTimeObjects($data);
    return $dates['start']->format('l');
  }

  /**
   * Get class description.
   *
   * @param array $data
   *   Class data.
   *
   * @return string
   *   Description.
   */
  private function getDescription(array $data) {
    return '';
  }

  /**
   * Get class start date.
   *
   * @param array $data
   *   Class data.
   *
   * @return string
   *   Start date.
   *
   * @throws \Exception
   */
  private function getStartDate(array $data) {
    $dates = $this->getTimeObjects($data);
    return $dates['start']->format('F j, Y');
  }

  /**
   * Get class end date.
   *
   * @param array $data
   *   Class data.
   *
   * @return string
   *   End date.
   *
   * @throws \Exception
   */
  private function getEndDate(array $data) {
    $dates = $this->getTimeObjects($data);
    return $dates['end']->format('F j, Y');
  }

  /**
   * Get class start time.
   *
   * @param array $data
   *   Class data.
   *
   * @return string
   *   Start time.
   *
   * @throws \Exception
   */
  private function getStartTime(array $data) {
    $dates = $this->getTimeObjects($data);
    return $dates['start']->format('G:i');
  }

  /**
   * Get class end time.
   *
   * @param array $data
   *   Class data.
   *
   * @return string
   *   End time.
   *
   * @throws \Exception
   */
  private function getEndTime(array $data) {
    $dates = $this->getTimeObjects($data);
    return $dates['end']->format('G:i');
  }

  /**
   * Get time objects from class.
   *
   * @param array $data
   *   Class data.
   *
   * @return array
   *   Array with date objects.
   *
   * @throws \Exception
   */
  private function getTimeObjects(array $data) {
    $date = \DateTime::createFromFormat(
      self::API_DATE_FORMAT,
      $data['date'],
      new \DateTimeZone(self::API_TIMEZONE)
    );

    $sourceTimes = explode('-', $data['time']);

    $sourceTimeStart = date_parse($sourceTimes[0]);
    if (!$sourceTimeStart) {
      throw new \Exception(sprintf('Failed to parse time %s', $sourceTimes[0]));
    }

    $sourceTimeEnd = date_parse($sourceTimes[1]);
    if (!$sourceTimeEnd) {
      throw new \Exception(sprintf('Failed to parse time %s', $sourceTimes[1]));
    }

    $dateStart = clone($date);
    $dateEnd = clone($date);

    $dateStart->setTime($sourceTimeStart['hour'], $sourceTimeStart['minute']);
    $dateEnd->setTime($sourceTimeEnd['hour'], $sourceTimeEnd['minute']);

    return [
      'start' => $dateStart,
      'end' => $dateEnd,
    ];
  }

}
