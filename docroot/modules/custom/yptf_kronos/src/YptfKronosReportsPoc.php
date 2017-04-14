<?php

namespace Drupal\yptf_kronos;

use Drupal\mindbody_cache_proxy\MindbodyCacheProxy;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Logger\LoggerChannelInterface;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Drupal\mindbody\MindbodyClient;

/**
 * Mindbody Examples.
 *
 * $service = \Drupal::service('ymca_training_reports.poc');
 * $service->poc();
 *
 * @package Drupal\mindbody
 */
class YptfKronosReportsPoc {

  /**
   * Config factory.
   *
   * @var ConfigFactory
   */
  protected $configFactory;

  /**
   * Proxy.
   *
   * @var MindbodyCacheProxy
   */
  protected $proxy;

  /**
   * Mindbody Credentials.
   *
   * @var array
   */
  protected $credentials;

  /**
   * The logger channel.
   *
   * @var LoggerChannelInterface
   */
  protected $logger;

  /**
   * The dates for report.
   */
  protected $dates;


  /**
   * The Kronos data report.
   */
  protected $kronos_data;

  /**
   * The MindBody data report.
   */
  protected $mindbody_data;

  /**
   * The StaffIDs.
   */
  protected $staffIDs;

  /**
   * YmcaMindbodyExamples constructor.
   *
   * @param MindbodyCacheProxy $proxy
   *   Cache proxy.
   * @param LoggerChannelInterface $logger
   *   Logger factory.
   */
  public function __construct(ConfigFactory $config_factory, MindbodyCacheProxy $proxy, LoggerChannelInterface $logger) {
    $this->configFactory = $config_factory;
    $this->proxy = $proxy;
    $this->logger = $logger;
    $this->credentials = $this->configFactory->get('mindbody.settings');
  }

  /**
   * Calculate/compare data from Kronos & reports.
   */
  public function poc() {
    // Get Kronos data.
    $kronos_file = drupal_get_path('module', 'ymca_training_reports') . '/files/kronos_data_20161231_megan.json';
    $kronos_data_raw = file_get_contents($kronos_file);
    $kronos_data = json_decode($kronos_data_raw);

    $trainer_reports = [];
    $location_reports = [];

    // Calculate Workforce Kronos data.
    foreach ($kronos_data as $item) {
      if ($item->job == 'PT (one on one and buddy)') {
        $staff_id = $item->empNo;
        // @TODO should be used ID from mapping.
        $location_id = $item->locName;
        $trainer_reports[$staff_id]['wf_hours'] += $item->totalHours;
        $trainer_reports[$staff_id]['historical_hours'] += $item->historical;
        $trainer_reports[$staff_id]['name'] = $item->firstName . ' ' . $item->lastName;
        $location_reports[$location_id]['wf_hours'] += $item->totalHours;
        $location_reports[$location_id]['historical_hours'] += $item->historical;
        $location_reports[$location_id]['name'] = $item->locName;
      }
    }

    // Calculate MB data.
    $user_credentials = [
      'Username' => $this->credentials->get('user_name'),
      'Password' => $this->credentials->get('user_password'),
      'SiteIDs' => [$this->credentials->get('site_id')],
    ];
    /*$params = [
      'StaffCredentials' => $user_credentials,
    ];*/

    // Figure out StuffID.
    //$results = $this->proxy->call('StaffService', 'GetStaff', $params, FALSE);

    $params = [
      //'UserCredentials' => $user_credentials,
      'StaffCredentials' => $user_credentials,
      // date('Y-m-d\TH:i:s', $booking_data['start_time']),
      //'StartDate' => '2016-12-19T00:00:00',
      'StartDate' => '2016-12-18T00:00:00',
      'EndDate' => '2016-12-31T23:59:59',
      'StaffIDs' => array_keys($trainer_reports),
    ];

    $result = $this->proxy->call('AppointmentService', 'GetStaffAppointments', $params, FALSE);
    $mb_data = $result->GetStaffAppointmentsResult->Appointments->Appointment;

    foreach ($mb_data as $item) {
      // PT - $item->Program->ID == 2
      // BT - $item->Program->ID == 4
      if ($item->Program->ID == 4){
        // @TODO For BT, skip every 2nd item based on start/end date + time(try to find better property.)
        $datetime1 = date_create($item->StartDateTime);
        $datetime2 = date_create($item->EndDateTime);
        $interval = date_diff($datetime1, $datetime2);
        $hours = (int)$interval->format("%h");
        $minutes = (int)$interval->format("%i");
        // Convert minutes to hours.
        $diff = $hours + $minutes / 60;

        $staff_id = $item->Staff->ID;
        // @TODO should be used ID from mapping.
        $location_id = str_replace(' YMCA', '',$item->Location->Name);

        $trainer_reports[$staff_id]['mb_hours'] += $diff;
        $location_reports[$location_id]['mb_hours'] += $diff;
      }
    }

    // Calculate variance.
    foreach ($trainer_reports as &$row) {
      $row['variance'] = number_format((1 - $row['mb_hours'] / $row['wf_hours']) * 100, 2);
    }
    foreach ($location_reports as &$row) {
      $row['variance'] = number_format((1 - $row['mb_hours'] / $row['wf_hours']) * 100, 2);
    }

    dpm($trainer_reports);
  }

}
