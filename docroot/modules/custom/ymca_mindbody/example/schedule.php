<?php
// @codingStandardsIgnoreFile
require_once('../src/MindBodyAPI.php');

?>
<html>
<head>
  <title>YMCA training demo</title>
</head>
<body>
<?php

// Credentials.
$sourcename = 'YMCAoftheGreaterTwinCities';
$password = 'W4aW8jPH1tR1KbdSEyDK1jdiQfY=';
$site_id = '-99';
$user_name = 'Siteowner';
$user_password = 'apitest1234';

$location_value = '';
if (!empty($_POST['location'])) {
  $location_value = $_POST['location'];
}

$program_value = '';
if (!empty($_POST['program'])) {
  $program_value = $_POST['program'];
}

$session_type_value = '';
if (!empty($_POST['session_type'])) {
  $session_type_value = $_POST['session_type'];
}

$trainer_value = '';
if (!empty($_POST['trainer'])) {
  $trainer_value = $_POST['trainer'];
}


$mb_site = new MindBodyAPI('SiteService', TRUE);
$mb_site->setCredentials($sourcename, $password, array($site_id));
$mb_staff = new MindBodyAPI('StaffService', TRUE);
$mb_staff->setCredentials($sourcename, $password, array($site_id));
$mb_app = new MindBodyAPI('AppointmentService', TRUE);
$mb_app->setCredentials($sourcename, $password, array($site_id));
$mb_class = new MindBodyAPI('ClassService', TRUE);
$mb_class->setCredentials($sourcename, $password, array($site_id));
$mb_client = new MindBodyAPI('ClientService', TRUE);
$mb_client->setCredentials($sourcename, $password, array($site_id));


// Retrieve client by ID.
$response = $mb_client->call('GetClients', array('ClientIDs' => array('777777')));

// Add new client.
/*$required_fields = $mb_client->call('GetRequiredClientFields', array());
$new_client = array('Clients' => array('Client' => array(
  'NewID' => '777777',
  'FirstName' => 'Alexfirstname',
  'LastName' => 'Alexlastname',
  'Email' => 'alexemail@gmail.com',
  'AddressLine1' => 'test',
  'City' => 'test',
  'PostalCode' => 'test',
  'ReferredBy' => 'test',
  'BirthDate' => '2009-03-13T22:16:00',
  'State' => 'State',
  'MobilePhone' => '312312312',
)));
$response = $mb_client->call('AddOrUpdateClients', $new_client);*/

// Update client.
/*$updated_client = array('Clients' => array('Client' => array(
  'ID' => '777777',
  'FirstName' => 'Alexfirstname updated2',
  'LastName' => 'Alexlastname updated2',
  'Email' => 'alexemail@gmail.com',
  'AddressLine1' => 'test',
  'City' => 'test',
  'PostalCode' => 'test',
  'ReferredBy' => 'test',
  'BirthDate' => '2009-03-13T22:16:00',
  'State' => 'State',
  'MobilePhone' => '312312312',
)));
$response = $mb_client->call('AddOrUpdateClients', $updated_client);*/

// Create order.
https://developers.mindbodyonline.com/Develop/PurchaseServiceAppointment


?>
<form id="trainings" method="POST">
  <h3>Which Location?</h3>
  <p>
  <?php
    $locations = $mb_site->call('GetLocations', array());
    foreach ($locations->GetLocationsResult->Locations->Location as $location) {
      if ($location->HasClasses != TRUE) {
        continue;
      }
      $checked = (($location_value == $location->ID) ? 'checked="checked"' : '');
      echo '<input type="radio" name="location" value="' . $location->ID . '" ' . $checked . '>' . $location->Name . '<Br>';
    }
  ?>
  </p>

  <?php if (!empty($location_value)) : ?>
    <h3>What are you looking for?</h3>
    <p>
      <?php
        $programs = $mb_site->call('GetPrograms', array('OnlineOnly' => FALSE, 'ScheduleType' => 'Appointment'));
        foreach ($programs->GetProgramsResult->Programs->Program as $program) {
          $checked = (($program_value == $program->ID) ? 'checked="checked"' : '');
          echo '<input type="radio" name="program" value="' . $program->ID . '" ' . $checked . '>' . $program->Name . '<Br>';
        }
      ?>
    </p>
  <?php endif; ?>

  <?php if (!empty($location_value) && !empty($program_value)) : ?>
    <h3>Which training?</h3>
    <p>
      <?php
      $session_types = $mb_site->call('GetSessionTypes', array('OnlineOnly' => FALSE, 'ProgramIDs' => array($program_value)));
      foreach ($session_types->GetSessionTypesResult->SessionTypes->SessionType as $type) {
        $checked = (($session_type_value == $type->ID) ? 'checked="checked"' : '');
        echo '<input type="radio" name="session_type" value="' . $type->ID . '" ' . $checked . '>' . $type->Name . '<Br>';
      }
      ?>
    </p>
  <?php endif; ?>

  <?php if (!empty($location_value) && !empty($program_value) && !empty($session_type_value)) : ?>
    <h3>With whom?</h3>
    <!--<p><i>* NOTE: MINDBODY API doesn't support filtering staff by location without specific date and time. <br />
        That's why we see all trainers, even courts. <br />
        <a href="https://goo.gl/I9uNY2" target="_blank">Screenshot</a> |
        <a href="https://developers.mindbodyonline.com/Develop/StaffService" target="_blank">API Docs</a></i></p>-->
    <p>
      <select name="trainer">
        <option value="all">All</option>
        <?php
        $booking_params = array(
          'UserCredentials' => array(
            'Username' => $user_name,
            'Password' => $user_password,
            'SiteIDs' => array(
              $site_id,
            ),
          ),
          'SessionTypeIDs' => array($session_type_value),
          'LocationIDs' => array($location_value),
        );
        $bookable = $mb_app->call('GetBookableItems', $booking_params);

        $staff_list = array();
        foreach ($bookable->GetBookableItemsResult->ScheduleItems->ScheduleItem as $bookable_item) {
          $photo = $mb_staff->call('GetStaffImgURL', array('StaffID' => $bookable_item->Staff->ID));
          $staff_list[$bookable_item->Staff->ID] = $bookable_item->Staff;
        }

        foreach ($staff_list as $staff) {
          $checked = (($trainer_value == $staff->ID) ? 'selected' : '');
          echo '<option ' . $checked . ' value="' . $staff->ID . '">' . $staff->Name . '</option>';
        }
        ?>
      </select>
    </p>
  <?php endif; ?>

  <?php if (!empty($location_value) && !empty($program_value) && !empty($session_type_value) && !empty($trainer_value)) : ?>
    <h3>When?</h3>
    <p>
      [form with date, time and week days]<br />
      <i>
        * Will be implemented.
        </i>
    </p>
  <?php endif; ?>

  <?php if (!empty($location_value) && !empty($program_value) && !empty($session_type_value) && !empty($trainer_value)) : ?>
    <h3>Search results</h3>
    <p>
      <?php
      $booking_params = array(
        'UserCredentials' => array(
          'Username' => $user_name,
          'Password' => $user_password,
          'SiteIDs' => array(
            $site_id,
          ),
        ),
        'SessionTypeIDs' => array($session_type_value),
        'LocationIDs' => array($location_value),
      );

      if (!empty($trainer_value) && $trainer_value != 'all') {
        $booking_params['StaffIDs'] = array($trainer_value);
      }
      $booking_params['StartDate'] = date('Y-m-d', strtotime('+ 1 day'));
      $booking_params['EndDate'] = date('Y-m-d', strtotime('+ 4 day'));

      $bookable = $mb_app->call('GetBookableItems', $booking_params);
      $var = $mb_app->client->__getLastRequest();
      $var2 = $mb_app->client->__getLastResponse();

      if (count($bookable->GetBookableItemsResult->ScheduleItems->ScheduleItem) == 1) {
        $bookable_item = $bookable->GetBookableItemsResult->ScheduleItems->ScheduleItem;
        echo $bookable_item->Staff->Name . ' | ' . date('m/d/Y h:i a', strtotime($bookable_item->StartDateTime)) . ' - ' . date('m/d/Y h:i a', strtotime($bookable_item->EndDateTime)) . '<br />';
      }
      else {
        foreach ($bookable->GetBookableItemsResult->ScheduleItems->ScheduleItem as $bookable_item) {
          echo $bookable_item->Staff->Name . ' | ' . date('m/d/Y h:i a', strtotime($bookable_item->StartDateTime)) . ' - ' . date('m/d/Y h:i a', strtotime($bookable_item->EndDateTime)) . '<br />';
        }
      }
      ?>
    </p>
  <?php endif; ?>

  <p><input type="submit"></p>
</form>
<a href="https://clients.mindbodyonline.com/classic/ws?studioid=-99&stype=-9" target="_blank">Original form for Personal Tainings</a>
</body>
</html>
