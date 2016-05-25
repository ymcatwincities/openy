<?php

namespace Drupal\ymca_google;

/**
 * Class GooglePush.
 *
 * @package Drupal\ymca_google
 */
class GooglePush {


  const APPLICATION_NAME = 'groupx-to-gcal-sync';
  const CREDENTIALS_PATH = __DIR__ . '/calendar-mvp.json';
  const CLIENT_SECRET_PATH = __DIR__ . '/cs.json';
  const SCOPES = \Google_Service_Calendar::CALENDAR;
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
  private $wrapper;

  /**
   * GooglePush constructor.
   */
  public function __construct(GcalGroupexWrapperInterface $wrapper) {
    $this->calendarId = '7rrsac9rvavuu68e5cmdho7di0@group.calendar.google.com';
    // Get the API client and construct the service object.
    $this->googleClient = $this->getClient();
    $this->calService = new \Google_Service_Calendar($this->googleClient);
    $this->calEvents = $this->calService->events;

    $this->wrapper = $wrapper;
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
//    $this->allEvents = $this->wrapper->getDestinationEntitiesFromProxy();
    
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

  /**
   * Returns an authorized API client.
   *
   * @return \Google_Client
   *   The authorized client object
   */
  private function getClient() {
    $client = new \Google_Client();
    $client->setApplicationName(GooglePush::APPLICATION_NAME);
    $client->setScopes(GooglePush::SCOPES);
    $client->setAuthConfigFile(GooglePush::CLIENT_SECRET_PATH);
    $client->setAccessType('offline');

    // Load previously authorized credentials from a file.
    $credentialsPath = $this->expandHomeDirectory(GooglePush::CREDENTIALS_PATH);
    if (file_exists($credentialsPath)) {
      // @todo rewrite to config.
      $accessToken = file_get_contents($credentialsPath);
    }
    else {
      // Request authorization from the user.
      $authUrl = $client->createAuthUrl();
      printf("Open the following link in your browser:\n%s\n", $authUrl);
      print 'Enter verification code: ';
      $authCode = trim(fgets(STDIN));

      // Exchange authorization code for an access token.
      $accessToken = $client->authenticate($authCode);

      // Store the credentials to disk.
      if (!file_exists(dirname($credentialsPath))) {
        // @todo rewrite to config.
        mkdir(dirname($credentialsPath), 0700, TRUE);
      }
      file_put_contents($credentialsPath, $accessToken);
      printf("Credentials saved to %s\n", $credentialsPath);
    }
    // @todo rewrite to config.
    $client->setAccessToken($accessToken);

    // Refresh the token if it's expired.
    if ($client->isAccessTokenExpired()) {
      $client->refreshToken($client->getRefreshToken());
      // @todo rewrite to config.
      file_put_contents($credentialsPath, $client->getAccessToken());
    }
    return $client;
  }

  /**
   * Expands the home directory alias '~' to the full path.
   *
   * @param string $path
   *   The path to expand.
   *
   * @return string
   *   The expanded path.
   */
  private function expandHomeDirectory($path) {
    $homeDirectory = getenv('HOME');
    if (empty($homeDirectory)) {
      $homeDirectory = getenv("HOMEDRIVE") . getenv("HOMEPATH");
    }
    return str_replace('~', realpath($homeDirectory), $path);
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
