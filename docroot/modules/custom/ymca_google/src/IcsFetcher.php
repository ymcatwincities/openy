<?php

namespace Drupal\ymca_google;

use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\ymca_mappings\LocationMappingRepository;
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
    $this->looger = $logger;
    $this->client = $client;
    $this->locationRepository = $location_repository;
  }

  /**
   * {@inheritdoc}
   */
  public function fetch(array $args) {
    $locations = $this->locationRepository->loadAllGroupexIds();
    $classes = [];

    foreach ($locations as $id) {
      try {
        $response = $this->client->request('GET', self::ICS_API_PATH . '/' . $id);
        $body = $response->getBody();
        $classes[$id] = json_decode($body->getContents());
      }
      catch (\Exception $e) {
        $msg = 'Failed to get response from Groupex ICS API for location %location.';
        $this->logger->critical(
          $msg,
          [
            '%location' => $id,
          ]
        );
      }
    }
  }

}
