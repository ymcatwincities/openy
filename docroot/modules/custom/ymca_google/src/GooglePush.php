<?php

namespace Drupal\ymca_google;

use Drupal\Component\Utility\Timer;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\ymca_groupex_google_cache\Entity\GroupexGoogleCache;
use Drupal\ymca_groupex\DrupalProxy;

/**
 * Class GooglePush.
 *
 * @package Drupal\ymca_google
 */
class GooglePush {

  /**
   * Date format for RRULE.
   */
  const RRULE_DATE = 'Ymd\THis\Z';

  /**
   * Default timezone for calendars.
   */
  const TZ = 'America/Chicago';

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
   * Logger Factory.
   *
   * @var LoggerChannelFactoryInterface
   */
  protected $loggerFactory;

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
   * Google calendars list.
   *
   * @var array
   */
  protected $calendars = [];

  /**
   * Query factory.
   *
   * @var QueryFactory
   */
  protected $query;

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
   * @param QueryFactory $query
   *   Query factory.
   */
  public function __construct(GcalGroupexWrapperInterface $data_wrapper, ConfigFactory $config_factory, LoggerChannelFactoryInterface $logger, EntityTypeManager $entity_type_manager, DrupalProxy $proxy, QueryFactory $query) {
    $this->dataWrapper = $data_wrapper;
    $this->configFactory = $config_factory;
    $this->logger = $logger->get(GcalGroupexWrapper::LOGGER_CHANNEL);
    $this->loggerFactory = $logger;
    $this->entityTypeManager = $entity_type_manager;
    $this->proxy = $proxy;
    $this->query = $query;

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
      Timer::start($op);
      $processed[$op] = 0;

      /** @var GroupexGoogleCache $entity */
      foreach ($entities as $entity) {

        // Refresh the token if it's expired.
        if ($this->googleClient->isAccessTokenExpired()) {
          $this->logger->info('Token is expired. Refreshing...');

          $this->googleClient->refreshToken($this->googleClient->getRefreshToken());
          $editable = $this->configFactory->getEditable('ymca_google.token');
          $editable->set('credentials', json_decode($this->googleClient->getAccessToken(), TRUE));
          $editable->save();
        }

        $gcal_id = $this->getCalendarIdByName($entity->field_gg_location->value);
        if (!$gcal_id) {
          // Failed to get calendar ID. All errors are logged. Continue with next event.
          continue;
        }

        switch ($op) {
          case 'update':
            $event = $this->drupalEntityToGcalEvent($entity);
            if (!$event) {
              break;
            }

            try {
              $this->calEvents->update(
                $gcal_id,
                $entity->field_gg_gcal_id->value,
                $event
              );

              $processed[$op]++;
              // Saving updated entity only when it was pushed successfully.
              $entity->save();
            }
            catch (\Google_Service_Exception $e) {
              if ($e->getCode() == 403) {
                $message = 'Google_Service_Exception [%op]: %message';
                $this->logger->error(
                  $message,
                  [
                    '%message' => $e->getMessage(),
                    '%op' => $op,
                  ]
                );
                $this->logStats($op, $processed);
                if (strstr($e->getMessage(), 'Rate Limit Exceeded')) {
                  // Rate limit exceeded, retry. @todo limit number of retries.
                  return;
                }
              }
              else {
                $message = 'Google Service Exception for operation %op for Entity: %uri : %message';
                $this->loggerFactory->get('GroupX_CM')->error(
                  $message,
                  [
                    '%op' => $op,
                    '%uri' => $entity->toUrl('canonical', ['absolute' => TRUE])->toString(),
                    '%message' => $e->getMessage()
                  ]
                );
                $this->logStats($op, $processed);
              }

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
                $gcal_id,
                $entity->field_gg_gcal_id->value
              );

              $storage = $this->entityTypeManager->getStorage('groupex_google_cache');
              $storage->delete([$entity]);

              $processed[$op]++;
            }
            catch (\Google_Service_Exception $e) {
              if ($e->getCode() == 403) {
                $message = 'Google_Service_Exception [%op]: %message';
                $this->logger->error(
                  $message,
                  [
                    '%message' => $e->getMessage(),
                    '%op' => $op,
                  ]
                );
                $this->logStats($op, $processed);
                if (strstr($e->getMessage(), 'Rate Limit Exceeded')) {
                  // Rate limit exceeded, retry. @todo limit number of retries.
                  return;
                }
              }
              else {
                $message = 'Google Service Exception for operation %op for Entity: %uri : %message';
                $this->loggerFactory->get('GroupX_CM')->error(
                  $message,
                  [
                    '%op' => $op,
                    '%uri' => $entity->toUrl('canonical', ['absolute' => TRUE])->toString(),
                    '%message' => $e->getMessage()
                  ]
                );
                $this->logStats($op, $processed);
              }

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
            if (!$event) {
              break;
            }

            try {
              $event = $this->calEvents->insert($gcal_id, $event);

              $entity->set('field_gg_gcal_id', $event->getId());
              $entity->save();

              $processed[$op]++;
            }
            catch (\Google_Service_Exception $e) {
              if ($e->getCode() == 403) {
                $message = 'Google_Service_Exception [%op]: %message';
                $this->logger->error(
                  $message,
                  [
                    '%message' => $e->getMessage(),
                    '%op' => $op,
                  ]
                );
                $this->logStats($op, $processed);
                if (strstr($e->getMessage(), 'Rate Limit Exceeded')) {
                  // Rate limit exceeded, retry. @todo limit number of retries.
                  return;
                }
              }
              else {
                $message = 'Google Service Exception for operation %op for Entity: %uri : %message';
                $this->loggerFactory->get('GroupX_CM')->error(
                  $message,
                  [
                    '%op' => $op,
                    '%uri' => $entity->toUrl('canonical', ['absolute' => TRUE])->toString(),
                    '%message' => $e->getMessage()
                  ]
                );
                $this->logStats($op, $processed);
              }

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

      $this->logStats($op, $processed);

    }

    // Mark this step as done in the schedule.
    $this->dataWrapper->next();

  }

  /**
   * Get Gcal ID by it's name (summary).
   *
   * @param string $name
   *   Calendar name (summary).
   *
   * @return bool|mixed
   *   Calendar ID.
   */
  protected function getCalendarIdByName($name) {
    // Return ID from cache if exists.
    if (array_key_exists($name, $this->calendars)) {
      return $this->calendars[$name];
    }

    // There is no calendar in the cache. Let's get data form the server.
    $raw_calendars = $this->getRawCalendars();
    if ($raw_calendars === FALSE) {
      return FALSE;
    }

    foreach ($raw_calendars as $raw_calendar) {
      $this->calendars[$raw_calendar->summary] = $raw_calendar->id;
    }

    // Check the cache again.
    if (array_key_exists($name, $this->calendars)) {
      return $this->calendars[$name];
    }

    // There is no calendar on the server. Let's create it.
    if ($id = $this->createCalendar($name)) {
      $this->calendars[$name] = $id;
      return $id;
    }

    return FALSE;
  }

  /**
   * Log.
   *
   * @param string $op
   *   Operation.
   * @param array $processed
   *   Processed.
   *
   * @throws \Exception
   */
  private function logStats($op, $processed) {
    $data = $this->dataWrapper->getProxyData();
    $schedule = $this->dataWrapper->getSchedule();
    $timeZone = new \DateTimeZone('UTC');
    $current = $schedule['current'];

    $startDateTime = DrupalDateTime::createFromTimestamp($schedule['steps'][$current]['start'], $timeZone);
    $startDate = $startDateTime->format('c');

    $endDateTime = DrupalDateTime::createFromTimestamp($schedule['steps'][$current]['end'], $timeZone);
    $endDate = $endDateTime->format('c');

    $message = 'Stats: op - %op, items - %items, processed - %processed, success - %success%. Time - %time. Time frame: %start - %end. Source data: %source. ';
    $this->logger->info(
      $message,
      [
        '%op' => $op,
        '%items' => count($data[$op]),
        '%time' => Timer::read($op),
        '%start' => $startDate,
        '%end' => $endDate,
        '%source' => count($this->dataWrapper->getSourceData()),
        '%processed' => $processed[$op],
        '%success' => count($data[$op]) == 0 ? '100%' : $processed[$op] * 100 / count($data[$op]),
      ]
    );
    Timer::stop($op);
  }

  /**
   * Convert cached entity to an event.
   *
   * @param GroupexGoogleCache $entity
   *   Entity.
   *
   * @return \Google_Service_Calendar_Event
   *   Event.
   */
  private function drupalEntityToGcalEvent(GroupexGoogleCache $entity) {
    $groupex_id = $entity->field_gg_class_id->value;

    $field_date = $entity->get('field_gg_date');
    $list_date = $field_date->getValue();

    $description = '';
    $instructor = '';
    $default = trim($entity->field_gg_instructor->value);
    if (empty($default)) {
      $sub_instructor = trim($entity->field_gg_sub_instructor->value);
      if (empty($sub_instructor)) {
        $original_instructor = trim($entity->field_gg_orig_instructor->value);
        if (!empty($original_instructor)) {
          $instructor = $original_instructor;
        }
      }
      else {
        $instructor = $sub_instructor;
      }
    }
    else {
      $instructor = $default;
    }

    if (!empty($instructor)) {
      $description = 'Instructor: ' . $instructor . "\n\n";
    }

    $description .= strip_tags(trim(html_entity_decode($entity->field_gg_description->value)));
    $location = trim($entity->field_gg_location->value);
    $summary = trim($entity->field_gg_title->value);

    // Prepare objects.
    $timezone = new \DateTimeZone('UTC');
    $date_time = new \DateTime();
    $date_time->setTimezone($timezone);

    // Start of the event.
    $start_date_time = clone $date_time;
    $start_date_time->setTimestamp($entity->field_gg_timestamp_start->value);

    // End of the event.
    $end_date_time = clone $date_time;
    $end_date_time->setTimestamp($entity->field_gg_timestamp_end->value);

    // Create Google event.
    $event = new \Google_Service_Calendar_Event([
      'summary' => $summary,
      'location' => $location,
      'description' => $description,
      'start' => [
        'dateTime' => $start_date_time->format(DATETIME_DATETIME_STORAGE_FORMAT),
        'timeZone' => 'UTC',
      ],
      'end' => [
        'dateTime' => $end_date_time->format(DATETIME_DATETIME_STORAGE_FORMAT),
        'timeZone' => 'UTC',
      ],
    ]);

    // Add logic for recurring events.
    if (count($list_date) > 1) {
      $rrule = [];

      // Get start timestamps of all events and sort them.
      $timestamps = [];
      foreach ($list_date as $id => $item) {
        $stamps = $this->proxy->buildTimestamps($item['value'], $entity->field_gg_time->value);
        $timestamps[$id] = $stamps['start'];
      }
      sort($timestamps, SORT_NUMERIC);

      // Check the frequency between dates. Exit if it's not weekly event.
      $diff = ($timestamps[1] - $timestamps[0]) / 604800;
      if (!is_int($diff)) {
        $this->logger->error('Got invalid interval %int for frequency for Groupex event %id', ['%int' => $diff, ['%id' => $groupex_id]]);
        return FALSE;
      }

      // Get datetime object of the last event (RRULE until).
      $until_date_time = clone $date_time;
      $until_date_time->setTimestamp(end($timestamps));
      $until_str = $until_date_time->format(self::RRULE_DATE);

      $rrule[] = "RRULE:FREQ=WEEKLY;INTERVAL=1;UNTIL=$until_str";

      /* Events may have excluded dates. In order to check whether the date
       * was excluded we need to check every event in the list of date field
       * of the cache entity. If date is not present - it's excluded. */

      // Get list of groupex dates in simple format.
      $dates = [];
      foreach ($timestamps as $timestamp_item) {
        $dates[] = $date_time->setTimestamp($timestamp_item)->format(self::RRULE_DATE);
      }

      // Loop over the each single week and check if the date exists in the event.
      $exclude = [];
      $current = $start_date_time->getTimestamp();
      while ($current <= $until_date_time->getTimestamp()) {
        $date_time->setTimestamp($current);
        $needle = $date_time->format(self::RRULE_DATE);
        if (!in_array($needle, $dates)) {
          $exclude[] = $needle;
        }

        // Go to next week.
        $date_time->add(new \DateInterval('P1W'));
        $current = $date_time->getTimestamp();
      }

      if (!empty($exclude)) {
        // We've got some excluded dates. Add them to event object.
        $rrule[] = "EXDATE:" . implode(',', $exclude);
      }

      $event['recurrence'] = $rrule;
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

  /**
   * Return raw list of calendars (except primary).
   *
   * @return array
   *   Array of Gcal list entries.
   */
  public function getRawCalendars() {
    $data = [];

    try {
      $list = $this->calService->calendarList->listCalendarList();
      while (TRUE) {
        foreach ($list->getItems() as $calendarListEntry) {
          // Do not return primary calendar.
          if (!$calendarListEntry->primary) {
            $data[] = $calendarListEntry;
          }
        }
        $pageToken = $list->getNextPageToken();
        if ($pageToken) {
          $optParams = array('pageToken' => $pageToken);
          try {
            $list = $this->calService->calendarList->listCalendarList($optParams);
          }
          catch (\Exception $e) {
            $msg = 'Failed to get the list of calendars. Message: %msg';
            $this->logger->error($msg, ['%msg' => $e->getMessage()]);
          }
        }
        else {
          break;
        }
      }
    }
    catch (\Exception $e) {
      $msg = 'Failed to get the list of calendars. Message: %msg';
      $this->logger->error($msg, ['%msg' => $e->getMessage()]);
      return FALSE;
    }

    return $data;
  }

  /**
   * Clear primary calendar.
   *
   * Tries 3 times and then exit.
   */
  public function clearPrimaryCalendar() {
    for ($i = 0; $i <= 2; $i++) {
      try {
        $this->calService->calendars->clear('primary');
        $this->logger->info('Primary calendar was cleared.');
        break;
      }
      catch (\Exception $e) {
        $message = 'Failed to clear primary calendar. Message: %msg';
        $this->logger->error($message, ['%msg' => $e->getMessage()]);
      }
    }
  }

  /**
   * Clear all calendars (except primary).
   */
  public function clearAllCalendars() {
    foreach ($this->getRawCalendars() as $item) {
      $this->deleteCalendar($item->id);
    }
  }

  /**
   * Remove calendar.
   *
   * Tries 3 times and then exit.
   *
   * @param string $id
   *   Calendar ID.
   */
  private function deleteCalendar($id) {
    for ($i = 0; $i <= 2; $i++) {
      try {
        $this->calService->calendars->delete($id);
        $this->logger->info('Calendar %id was deleted.', ['%id' => $id]);
        break;
      }
      catch (\Exception $e) {
        $message = 'Failed to delete the calendar %id. Message: %msg';
        $this->logger->error($message,
          [
            '%id' => $id,
            '%msg' => $e->getMessage()
          ]
        );
      }
    }
  }

  /**
   * Create google calendar.
   *
   * @param string $name
   *   Calendar summary.
   *
   * @return mixed
   *   Calendar ID.
   */
  private function createCalendar($name) {
    $calendar = new \Google_Service_Calendar_Calendar();
    $calendar->setSummary($name);
    $calendar->setTimeZone(self::TZ);

    try {
      $createdCalendar = $this->calService->calendars->insert($calendar);
      $id = $createdCalendar->getId();
      $this->logger->info('Calendar was created: id: %id, name: %name', ['%id' => $id, '%name' => $name]);
      return $id;
    }
    catch (\Exception $e) {
      $this->logger->error('Failed to create calendar with name: %name', ['%name' => $name]);
      return FALSE;
    }
  }

  /**
   * Remove all cached entities.
   */
  public function clearCache() {
    $ids = $this->query->get('groupex_google_cache')->execute();
    $chunks = array_chunk($ids, 10);
    $storage = $this->entityTypeManager->getStorage('groupex_google_cache');
    foreach ($chunks as $chunk) {
      $cache = GroupexGoogleCache::loadMultiple($chunk);
      $storage->delete($cache);
    }
  }

}
