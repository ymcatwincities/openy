<?php

namespace Drupal\ymca_google;

use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\ymca_mappings\Entity\Mapping;

/**
 * Class GooglePush.
 *
 * @package Drupal\ymca_google
 */
class GooglePush {

  /**
   * Wrapper to be used.
   *
   * @var GcalGroupexWrapperInterface
   */
  protected $dataWrapper;

  /**
   * Config Factory.
   *
   * @var ConfigFactory
   */
  protected $configFactory;

  /**
   * ID for Google Calendar.
   *
   * @var string
   */
  protected $calendarId;

  /**
   * Google Calendar Service.
   *
   * @var \Google_Service_Calendar
   */
  protected $calService;

  /**
   * Google Calendar Events Service.
   *
   * @var \Google_Service_Calendar_Events_Resource
   */
  protected $calEvents;

  /**
   * Google Client.
   *
   * @var \Google_Client
   */
  protected $googleClient;

  /**
   * Logger channel.
   *
   * @var LoggerChannelInterface
   */
  protected $logger;

  /**
   * GooglePush constructor.
   *
   * @param GcalGroupexWrapperInterface $data_wrapper
   *   Data wrapper.
   * @param ConfigFactory $config_factory
   *   Config Factory.
   * @param LoggerChannelFactoryInterface $logger
   *   Logger.
   */
  public function __construct(GcalGroupexWrapperInterface $data_wrapper, ConfigFactory $config_factory, LoggerChannelFactoryInterface $logger) {
    $this->dataWrapper = $data_wrapper;
    $this->configFactory = $config_factory;
    $this->logger = $logger->get('ymca_google');

    $settings = $this->configFactory->get('ymca_google.settings');
    $this->calendarId = $settings->get('calendar_id');

    // Get the API client and construct the service object.
    $this->googleClient = $this->getClient();
    $this->calService = new \Google_Service_Calendar($this->googleClient);
    $this->calEvents = $this->calService->events;
  }

  /**
   * Proceed all events collected by add methods.
   */
  public function proceed() {
    $data = $this->dataWrapper->getProxyData();
    foreach ($data as $op => $entities) {
      foreach ($entities as $entity) {
        $event = $this->drupalEntityToGcalEvent($entity);

        switch ($op) {
          case 'update':
            try {
              $this->calEvents->update(
                $this->calendarId,
                $event->getId(),
                $event
              );
            }
            catch (\Google_Service_Exception $e) {
              $msg = 'Error while updating event for entity [%id]: %msg';
              $this->logger->error($msg, [
                '%id' => $entity->id(),
                '%msg' => $e->getMessage(),
              ]);
            }
            break;

          case 'delete':
            try {
              $this->calEvents->delete(
                $this->calendarId,
                $event->getId()
              );
            }
            catch (\Google_Service_Exception $e) {
              $msg = 'Error while deleting event for entity [%id]: %msg';
              $this->logger->error($msg, [
                '%id' => $entity->id(),
                '%msg' => $e->getMessage(),
              ]);
            }
            break;

          case 'insert':
            try {
              $event = $this->calEvents->insert($this->calendarId, $event);
              $entity->set('field_gcal_id', $event->getId());
              $entity->save();
            }
            catch (\Google_Service_Exception $e) {
              $msg = 'Error while inserting event for entity [%id]: %msg';
              $this->logger->error($msg, [
                '%id' => $entity->id(),
                '%msg' => $e->getMessage(),
              ]);
            }
            break;
        }

      }
    }

  }

  /**
   * Convert mapping entity to an event.
   *
   * @param Mapping $entity
   *   Mapping entity.
   *
   * @return \Google_Service_Calendar_Event
   *   Event.
   */
  private function drupalEntityToGcalEvent(Mapping $entity) {
    // @todo Fix this code to work with recurring events.
    $recurring = FALSE;

    $field_date = $entity->get('field_groupex_date');
    $list_date = $field_date->getValue();
    if (count($list_date) > 1) {
      $recurring = TRUE;
      $this->logger->debug('We have got recurring event...');
    }

    $description = strip_tags(trim(html_entity_decode($entity->field_groupex_description->value)));
    $location = trim($entity->field_groupex_location->value);
    $summary = trim($entity->field_groupex_title->value);
    $start = $entity->field_timestamp_start->value;
    $end = $entity->field_timestamp_end->value;

    $timezone = new \DateTimeZone('UTC');
    $startDateTime = DrupalDateTime::createFromTimestamp($start, $timezone);
    $endDateTime = DrupalDateTime::createFromTimestamp($end, $timezone);

    $event = new \Google_Service_Calendar_Event([
      'summary' => $summary,
      'location' => $location,
      'description' => $description,
      'start' => [
        'dateTime' => $startDateTime->format(DATETIME_DATETIME_STORAGE_FORMAT),
        'timeZone' => 'UTC',
      ],
      'end' => [
        'dateTime' => $endDateTime->format(DATETIME_DATETIME_STORAGE_FORMAT),
        'timeZone' => 'UTC',
      ],
    ]);

    return $event;
  }

  /**
   * Returns an authorized API client.
   *
   * @return \Google_Client
   *   The authorized client object
   *
   * @see https://developers.google.com/google-apps/calendar/quickstart/php
   */
  private function getClient() {
    $settings = $this->configFactory->get('ymca_google.settings');
    $token = $this->configFactory->get('ymca_google.token');

    $client = new \Google_Client();
    $client->setApplicationName($settings->get('application_name'));
    $client->setScopes(\Google_Service_Calendar::CALENDAR);
    $client->setAuthConfig(json_encode($settings->get('auth_config')));
    $client->setAccessToken(json_encode($token->get('credentials')));

    $client->setAccessType('offline');

    // Refresh the token if it's expired.
    if ($client->isAccessTokenExpired()) {
      $client->refreshToken($client->getRefreshToken());
      $editable = $this->configFactory->getEditable('ymca_google.token');
      $editable->set('credentials', json_decode($client->getAccessToken(), TRUE));
      $editable->save();
    }

    return $client;
  }

}
