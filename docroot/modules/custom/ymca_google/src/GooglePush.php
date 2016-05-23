<?php
/**
 * @file
 * Google Push service.
 */
namespace Drupal\ymca_google;

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
   * @var \Google_Service_Calendar
   */
  private $calService;

  /**
   * @var \Google_Service_Calendar_Events_Resource
   */
  private $calEvents;

  /**
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
   * GooglePush constructor.
   */
  public function __construct() {
    $this->calendarId = '7rrsac9rvavuu68e5cmdho7di0@group.calendar.google.com';
    // Get the API client and construct the service object.
    $this->googleClient = $this->getClient();
    $this->calService = new \Google_Service_Calendar($this->googleClient);
    $this->calEvents = $this->calService->events;

    // Init basic array.
    $this->allEvents = [
      'update' => [],
      'create' => [],
      'delete' => []
    ];
  }

  /**
   * Populate array of events to be updated.
   * 
   * @param \Google_Service_Calendar_Event $event
   *
   * @return $this
   */
  public function addEventForUpdate(\Google_Service_Calendar_Event $event) {
    $this->allEvents['update'][] = $event;
    
    return $this;
  }

  /**
   * Populate array of events to be deleted.
   * 
   * @param \Google_Service_Calendar_Event $event
   *
   * @return $this
   */
  public function addEventForDelete(\Google_Service_Calendar_Event $event) {
    $this->allEvents['delete'][] = $event;

    return $this;
  }

  /**
   * Populate array of events to be created.
   *
   * @param \Google_Service_Calendar_Event $event
   *
   * @return $this
   */
  public function addEventForCreate(\Google_Service_Calendar_Event $event) {
    $this->allEvents['create'][] = $event;

    return $this;
  }

  /**
   * @return $this
   */
  public function proceed() {
    // @todo implement

    return $this;
  }

  /**
   * Create test event
   */
  public function createTestEvent() {

    $event = new \Google_Service_Calendar_Event(array(
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
      // 'recurrence' => array(
      //   'RRULE:FREQ=DAILY;COUNT=2'
      // ),
      // 'attendees' => array(
      //   array('email' => 'lpage@example.com'),
      //   array('email' => 'sbrin@example.com'),
      // ),
      // 'reminders' => array(
      //   'useDefault' => FALSE,
      //   'overrides' => array(
      //     array('method' => 'email', 'minutes' => 24 * 60),
      //     array('method' => 'popup', 'minutes' => 10),
      //   ),
      // ),
    ));

    $this->calEvents->insert($this->calendarId, $event);

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
        mkdir(dirname($credentialsPath), 0700, TRUE);
      }
      file_put_contents($credentialsPath, $accessToken);
      printf("Credentials saved to %s\n", $credentialsPath);
    }
    $client->setAccessToken($accessToken);

    // Refresh the token if it's expired.
    if ($client->isAccessTokenExpired()) {
      $client->refreshToken($client->getRefreshToken());
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
  
}