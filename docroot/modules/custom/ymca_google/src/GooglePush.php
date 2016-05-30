<?php

namespace Drupal\ymca_google;

use Drupal\Core\Config\ConfigFactory;

/**
 * Class GooglePush.
 *
 * @package Drupal\ymca_google
 */
class GooglePush {

  /**
   * ID for Google Calendar.
   *
   * @var string
   */
  private $calendarId;

  /**
   * Google Calendar Service.
   *
   * @var \Google_Service_Calendar
   */
  private $calService;

  /**
   * Google Calendar Events Service.
   *
   * @var \Google_Service_Calendar_Events_Resource
   */
  private $calEvents;

  /**
   * Google Client.
   *
   * @var \Google_Client
   */
  private $googleClient;

  /**
   * Keyed array of events to be processed.
   *
   * @var array
   */
  private $allEvents;

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
   * @var array
   */
  private $sourceEntities;

  /**
   * GooglePush constructor.
   *
   * @param GcalGroupexWrapperInterface $data_wrapper
   *   Data wrapper.
   * @param ConfigFactory $config_factory
   *   Config Factory.
   */
  public function __construct(GcalGroupexWrapperInterface $data_wrapper, ConfigFactory $config_factory) {
    $this->dataWrapper = $data_wrapper;
    $this->configFactory = $config_factory;

    $this->calendarId = '7rrsac9rvavuu68e5cmdho7di0@group.calendar.google.com';

    // Get the API client and construct the service object.
    $this->googleClient = $this->getClient();
    $this->calService = new \Google_Service_Calendar($this->googleClient);
    $this->calEvents = $this->calService->events;

  }

  /**
   * Populate array of events to be updated.
   *
   * @param \Google_Service_Calendar_Event $event
   *   Event to be added.
   *
   * @return $this
   *   Chaining.
   */
  public function addEventForUpdate(\Google_Service_Calendar_Event $event) {
    $this->allEvents['update'][$event->getId()] = $event;

    return $this;
  }

  /**
   * Populate array of events to be deleted.
   *
   * @param \Google_Service_Calendar_Event $event
   *   Event to be added.
   *
   * @return $this
   *   Chaining.
   */
  public function addEventForDelete(\Google_Service_Calendar_Event $event) {
    $this->allEvents['delete'][$event->getId()] = $event;

    return $this;
  }

  /**
   * Populate array of events to be created.
   *
   * @param \Google_Service_Calendar_Event $event
   *   Event to be added.
   *
   * @return string
   *   Returns key of the event within array.
   */
  public function addEventForCreate(\Google_Service_Calendar_Event $event) {
    if ($event->getId() == NULL) {
      $hash = spl_object_hash($event);
      $this->allEvents['insert'][$hash] = $event;
      return $hash;
    }
    else {
      $this->allEvents['insert'][$event->getId()] = $event;
      return $event->getId();
    }
  }

  /**
   * Proceed all events collected by add methods.
   *
   * @return $this
   *   Chaining.
   */
  public function proceed() {

   // @todo Prepopulate allEvents properly. 
//    // Init basic array.
//    $this->wrapper->getDrupalEntitiesFromSource();
    $this->sourceEntities = $this->wrapper->getProxyData();
    foreach ($this->sourceEntities as $id => $entity) {
      $this->allEvents['insert'][] = $this->drupaEntityToGcalEvent($entity);
    }
    
    foreach ($this->allEvents as $method => &$events) {
      switch ($method) {
        case 'update':
          /** @var \Google_Service_Calendar_Event $event */
          foreach ($events as $hash => &$event) {
            $event = $this->calEvents->update(
              $this->calendarId,
              $event->getId(),
              $event
            );
          }
          break;

        case 'delete':
          /** @var \Google_Service_Calendar_Event $event */
          foreach ($events as $hash => &$event) {
            $event = $this->calEvents->delete(
              $this->calendarId,
              $event->getId()
            );
          }
          break;

        case 'insert':
          /** @var \Google_Service_Calendar_Event $event */
          foreach ($events as $hash => &$event) {
            $event = $this->calEvents->insert($this->calendarId, $event);
          }
          break;
      }
    }
    return $this;
  }

  /**
   * Create test event.
   */
  public function createTestEvent() {

    $event = new \Google_Service_Calendar_Event(
      array(
        'summary' => 'Test from code',
        'location' => 'Kyiv, Ukraine, 01042',
        'description' => 'Description for test event from code.',
        'start' => array(
          'dateTime' => '2016-05-24T09:00:00-07:00',
          'timeZone' => 'UTC',
        ),
        'end' => array(
          'dateTime' => '2016-05-24T17:00:00-07:00',
          'timeZone' => 'UTC',
        ),
      )
    );

    $hash = $this->addEventForCreate($event);
    $this->proceed();

    return $this->allEvents['insert'][$hash]->getHtmlLink();
  }

  private function drupaEntityToGcalEvent($entity) {
    $event = new \Google_Service_Calendar_Event(
      array(
        // @todo add title to mapping.
        'summary' => $entity->id(),
        'location' => $entity->field_groupex_location->getValue(),
        'description' => $entity->field_groupex_description->getValue(),
        'start' => array(
          'dateTime' => '2016-05-24T09:00:00-07:00',
          'timeZone' => 'UTC',
        ),
        'end' => array(
          'dateTime' => '2016-05-24T17:00:00-07:00',
          'timeZone' => 'UTC',
        ),
      )
    );
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
   * Getter for all events. Can be used after proceed.
   *
   * @return array
   *   Array of events, sorted by method.
   */
  public function getAllEvents() {
    return $this->allEvents;
  }

}
