<?php

namespace Drupal\ymca_google;

use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\ymca_mappings\LocationMappingRepository;
use Drupal\ymca_sync\SyncerTerminateException;
use GuzzleHttp\Client;

/**
 * Class IcsFetcher.
 *
 * @package Drupal\ymca_google
 */
class IcsFetcher implements IcsFetcherInterface {

  /**
   * API path.
   */
  const ICS_API_PATH = 'http://www.groupexpro.com/gxp/ics/view/3';

  /**
   * Wrapper.
   *
   * @var \Drupal\ymca_google\GcalGroupexWrapperInterface
   */
  protected $wrapper;

  /**
   * The logger channel.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * Guzzle http client.
   *
   * @var \GuzzleHttp\Client
   */
  protected $client;

  /**
   * The locations repository.
   *
   * @var \Drupal\ymca_mappings\LocationMappingRepository
   */
  protected $locationRepository;

  /**
   * IcsFetcher constructor.
   *
   * @param \Drupal\ymca_google\GcalGroupexWrapperInterface $wrapper
   *   Data wrapper.
   * @param \Drupal\Core\Logger\LoggerChannelInterface $logger
   *   The logger channel.
   * @param \GuzzleHttp\Client $client
   *   Guzzle http client.
   * @param LocationMappingRepository $location_repository
   *   The locations repository.
   */
  public function __construct(GcalGroupexWrapperInterface $wrapper, LoggerChannelInterface $logger, Client $client, LocationMappingRepository $location_repository) {
    $this->wrapper = $wrapper;
    $this->logger = $logger;
    $this->client = $client;
    $this->locationRepository = $location_repository;
  }

  /**
   * {@inheritdoc}
   */
  public function fetch(array $args) {
    // Debug.
    if (FALSE) {
      $data = [
        (object) [
          'id' => '145945',
          'category' => 'Strength',
          'location' => 'Andover',
          'title' => 'BodyPump',
          'description' => 'Here long description...',
          'post_date' => '2012-09-05 09:41:09',
          'start_date' => '2012-09-10 08:20:00',
          'end_date' => '2012-09-10 09:20:00',
          'recurring' => 'weekly',
          'parent_id' => '',
          'instructor' => 'Rick Santiago',
          'location_id' => '26',
        ],
      ];

      $this->wrapper->setIcsData($data);
      return;
    }

    $locations = $this->locationRepository->loadAllGroupexIds();
    $classes = [];

    foreach ($locations as $id) {
      try {
        $response = $this->client->request('GET', self::ICS_API_PATH . '/' . $id);
        if (200 != $response->getStatusCode()) {
          $msg = sprintf('Got no 200 response from Groupex ICS API for location %s.', $id);
          $this->wrapper->terminate($msg);
        }

        $body = $response->getBody();
        $data = json_decode($body->getContents());

        // Add location ID to each class.
        foreach ($data as $class) {
          $class->location_id = $id;
          $classes[] = $class;
        }
      }
      catch (\Exception $e) {
        $msg = sprintf('Failed to get response from Groupex ICS API for location %s.', $id);
        $this->wrapper->terminate($msg);
      }
    }

    $this->wrapper->setIcsData($classes);
  }

}
