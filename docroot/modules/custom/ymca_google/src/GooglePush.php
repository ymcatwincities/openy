<?php

namespace Drupal\ymca_google;

use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\ymca_groupex\DrupalProxy;
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
   * Entity type manager.
   *
   * @var EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * Proxy.
   *
   * @var DrupalProxy
   */
  protected $proxy;

  /**
   * GooglePush constructor.
   *
   * @param GcalGroupexWrapperInterface $data_wrapper
   *   Data wrapper.
   * @param ConfigFactory $config_factory
   *   Config Factory.
   * @param LoggerChannelFactoryInterface $logger
   *   Logger.
   * @param EntityTypeManager $entity_type_manager
   *   Entity type manager.
   * @param DrupalProxy $proxy
   *   Proxy.
   */
  public function __construct(GcalGroupexWrapperInterface $data_wrapper, ConfigFactory $config_factory, LoggerChannelFactoryInterface $logger, EntityTypeManager $entity_type_manager, DrupalProxy $proxy) {
    $this->dataWrapper = $data_wrapper;
    $this->configFactory = $config_factory;
    $this->logger = $logger->get('ymca_google');
    $this->entityTypeManager = $entity_type_manager;
    $this->proxy = $proxy;

    $settings = $this->configFactory->get('ymca_google.settings');
    $this->calendarId = $settings->get('calendar_id');

    // Get the API client and construct the service object.
    $this->googleClient = $this->getClient();
    $this->calService = new \Google_Service_Calendar($this->googleClient);
    $this->calEvents = $this->calService->events;
  }

  /**
   * Clear calendar method. Only primary can be cleared here.
   */
  public function clear() {
    if ($this->calendarId != 'primary') {
      return;
    }
    $this->calService->calendars->clear($this->calendarId);
  }

  /**
   * Proceed all events collected by add methods.
   */
  public function proceed() {
    $data = $this->dataWrapper->getProxyData();
    foreach ($data as $op => $entities) {
      foreach ($entities as $entity) {

        switch ($op) {
          case 'update':
            $event = $this->drupalEntityToGcalEvent($entity);

            try {
              $this->calEvents->update(
                $this->calendarId,
                $entity->field_gcal_id->value,
                $event
              );

              $this->logger->info(
                'Groupex event (%id) has been updated.',
                ['%id' => $entity->field_groupex_class_id->value]
              );
            }
            catch (\Exception $e) {

              $msg = '%type : Error while updating event for entity [%id]: %msg';
              $this->logger->error($msg, [
                '%type' => get_class($e),
                '%id' => $entity->id(),
                '%msg' => $e->getMessage(),
              ]);
            }

            break;

          case 'delete':
            try {
              $this->calEvents->delete(
                $this->calendarId,
                $entity->field_gcal_id->value
              );

              $groupex_id = $entity->field_groupex_class_id->value;
              $storage = $this->entityTypeManager->getStorage('mapping');
              $storage->delete([$entity]);

              $this->logger->info(
                'Groupex event (%id) has been deleted.', ['%id' => $groupex_id]
              );
            }
            catch (\Exception $e) {
              $msg = 'Error while deleting event for entity [%id]: %msg';
              $this->logger->error($msg, [
                '%id' => $entity->id(),
                '%msg' => $e->getMessage(),
              ]);
            }

            break;

          case 'insert':
            $event = $this->drupalEntityToGcalEvent($entity);

            try {
              $event = $this->calEvents->insert($this->calendarId, $event);

              $entity->set('field_gcal_id', $event->getId());
              $entity->save();
            }
            catch (\Exception $e) {
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

    // Mark this step as done in the schedule.
    $this->dataWrapper->next();

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
    $groupex_id = $entity->field_groupex_class_id->value;

    $field_date = $entity->get('field_groupex_date');
    $list_date = $field_date->getValue();

    $description = '';
    $instructor = trim($entity->field_groupex_instructor->value);
    if (empty($instructor)) {
      $message = 'Failed to load instructor for Groupex event (%id)';
      $this->logger->error($message, ['%id' => $groupex_id]);
    }
    else {
      $description = 'Instructor: ' . $instructor . "\n\n";
    }

    $description .= strip_tags(trim(html_entity_decode($entity->field_groupex_description->value)));
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

    // Add logic for recurring events.
    if (count($list_date) > 1) {
      $time = $entity->field_groupex_time->value;

      // Get start timestamps of all events.
      $timestamps = [];
      foreach ($list_date as $id => $item) {
        $stamps = $this->proxy->buildTimestamps($item['value'], $time);
        $timestamps[$id] = $stamps['start'];
      }

      sort($timestamps, SORT_NUMERIC);

      // Get frequency.
      $diff = $timestamps[1] - $timestamps[0];

      // Get timestamp of the last event.
      $timezone = new \DateTimeZone('UTC');
      $dateTime = DrupalDateTime::createFromTimestamp(end($timestamps), $timezone);
      $until = $dateTime->format('Ymd\THis\Z');

      // Check whether it is daily event.
      if ($diff != 86400) {
        $message = 'Failed to get frequency on Groupex event (%id)';
        $this->logger->error(
          $message,
          ['%id' => $groupex_id]
        );
        return $event;
      }

      $event['recurrence'] = [
        'RRULE:FREQ=DAILY;UNTIL=' . $until,
      ];

    }

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
