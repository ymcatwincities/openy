<?php
/**
 * @file
 * Quickstart rework.
 */

define('APPLICATION_NAME', 'Google Calendar API PHP Quickstart');
define('CREDENTIALS_PATH', __DIR__ . '/calendar-mvp.json');
define('CLIENT_SECRET_PATH', __DIR__ . '/cs.json');
// If modifying these scopes, delete your previously saved credentials
// at ~/.credentials/calendar-php-quickstart.json.
define(
  'SCOPES',
  implode(
    ' ',
    array(
      Google_Service_Calendar::CALENDAR
    )
  )
);

/**
 * Returns an authorized API client.
 *
 * @return Google_Client
 *   The authorized client object
 */
function get_client() {
  $client = new Google_Client();
  $client->setApplicationName(APPLICATION_NAME);
  $client->setScopes(SCOPES);
  $client->setAuthConfigFile(CLIENT_SECRET_PATH);
  $client->setAccessType('offline');

  // Load previously authorized credentials from a file.
  $credentialsPath = expand_home_directory(CREDENTIALS_PATH);
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
function expand_home_directory($path) {
  $homeDirectory = getenv('HOME');
  if (empty($homeDirectory)) {
    $homeDirectory = getenv("HOMEDRIVE") . getenv("HOMEPATH");
  }
  return str_replace('~', realpath($homeDirectory), $path);
}

// Get the API client and construct the service object.
$client = get_client();
$service = new Google_Service_Calendar($client);

// Print the next 10 events on the user's calendar.
$calendarId = '7rrsac9rvavuu68e5cmdho7di0@group.calendar.google.com';
$optParams = array(
  'maxResults' => 10,
  'orderBy' => 'startTime',
  'singleEvents' => TRUE,
  'timeMin' => date('c'),
);
$results = $service->events->listEvents($calendarId, $optParams);
/** @var Google_Service_Calendar_Events_Resource $events */
$events = $service->events;
$event = new Google_Service_Calendar_Event(array(
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




$list = $service->calendarList->listCalendarList();


$events->insert('7rrsac9rvavuu68e5cmdho7di0@group.calendar.google.com', $event);

$out = '';
if (count($results->getItems()) == 0) {
  $out .= "No upcoming events found.\n";
}
else {
  $out .= "Upcoming events:\n";
  foreach ($results->getItems() as $event) {
    $start = $event->start->dateTime;
    if (empty($start)) {
      $start = $event->start->date;
    }

    $out .= sprintf("%s (%s)\n", $event->getSummary(), $start);
    $out .= '<pre>' . print_r($event, TRUE) . '</pre>';
  }
;
}
