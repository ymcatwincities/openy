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
  protected $kronosData;

  /**
   * The MindBody data report.
   */
  protected $mindbodyData;

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
    // @TODO: Get rid from mindbody $this->debug = TRUE.
    // Get Kronos data.
    if (!$kronos_data = $this->getKronosData()) {
      return;
    }
    $trainer_reports = [];
    $location_reports = [];

    // Calculate Workforce Kronos data.
    foreach ($kronos_data as $item) {
      if ($item->job == 'PT (one on one and buddy)') {
        $staff_id = $this->getMindbodyidbyStaffId($item->empNo);
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
    $cache_dir = \Drupal::service('file_system')->realpath(file_default_scheme() . "://");
    $cache_dir = $cache_dir . '/mb_reports';
    $file = $cache_dir . '/staff_ids.json';
    if (!file_exists($cache_dir)) {
      mkdir($cache_dir, 0764, TRUE);
      file_put_contents($file, json_encode($this->staffIDs));
    }

    // Calculate MB data.
    $user_credentials = [
      'Username' => $this->credentials->get('user_name'),
      'Password' => $this->credentials->get('user_password'),
      'SiteIDs' => [$this->credentials->get('site_id')],
    ];
    $params = [
      'StaffCredentials' => $user_credentials,
      'StartDate' => $this->dates['StartDate'],
      'EndDate' => $this->dates['EndDate'],
      'StaffIDs' => [0], //[0 => '100000154'], //Zero is for all staff. [0 => '100000154'],
      // 'StaffIDs' => array_keys($trainer_reports),
    ];
    // New service to add.
    // Saving a file with a path.
    $cache_dir = \Drupal::service('file_system')->realpath(file_default_scheme() . "://");
    $cache_dir = $cache_dir . '/mb_reports';
    $file = $cache_dir . '/WFC_' . $this->dates['EndDate'] . '.json';
    $mb_data_file = file_get_contents($file);
    if (!$mb_data_file) {
      $result = $this->proxy->call('AppointmentService', 'GetStaffAppointments', $params, FALSE);
      $mb_data = $result->GetStaffAppointmentsResult->Appointments->Appointment;

      if (!file_exists($cache_dir)) {
        mkdir($cache_dir, 0764, TRUE);
      }
      file_put_contents($file, json_encode($mb_data));
    }
    else {
      $mb_data = json_decode($mb_data_file);
    }

    $skip_bt = FALSE;
    foreach ($mb_data as $mb_id => $item) {
      // PT - $item->Program->ID == 2
      // BT - $item->Program->ID == 4.
      $datetime1 = date_create($item->StartDateTime);
      $datetime2 = date_create($item->EndDateTime);
      if ($item->Program->ID == 4) {
        if ($skip_bt) {
          $skip_bt = !$skip_bt;
          continue;
        }
      }
      // @TODO For BT, skip every 2nd item based on start/end date + time(try to find better property.)

      $interval = date_diff($datetime1, $datetime2);
      $hours = (int) $interval->format("%h");
      $minutes = (int) $interval->format("%i");
      // Convert minutes to hours.
      $diff = $hours + $minutes / 60;

      $staff_id = $item->Staff->ID;
      // @TODO should be used ID from mapping.
      $location_id = str_replace(' YMCA', '', $item->Location->Name);

      $trainer_reports[$staff_id]['mb_hours'] += $diff;
      $location_reports[$location_id]['mb_hours'] += $diff;

      // Skip second BT.
      $skip_bt = !$skip_bt;

    }

    // Calculate variance.
    foreach ($trainer_reports as &$row) {
      $row['variance'] = number_format((1 - $row['mb_hours'] / $row['wf_hours']) * 100, 2);
    }
    foreach ($location_reports as &$row) {
      $row['variance'] = number_format((1 - $row['mb_hours'] / $row['wf_hours']) * 100, 2);
    }

    dpm($location_reports);
  }

  /**
   * Get Kronos Data from file.
   *
   * @return array|bool
   *   List of trainers hours.
   */
  public function getKronosData() {
    // @TODO: What day we need to run cron?
    $kronos_report_day = 'last Saturday';
    $kronos_shift_days = ['', ' -7 days'];
    if ($week_day = date("w") < 2) {
      $kronos_shift_days = [' -7 days', ' -14 days'];
    }
    $kronos_data_raw = $kronos_file = '';
    foreach ($kronos_shift_days as $shift) {
      $kronos_file_name_date = date('Y-m-d', strtotime($kronos_report_day . $shift));
      $kronos_path_to_file = \Drupal::service('file_system')->realpath(file_default_scheme() . "://");
      $kronos_file = $kronos_path_to_file . '/wf_reports/WFC_' . $kronos_file_name_date . '.json';
      $kronos_data_raw = file_get_contents($kronos_file);
      if (!$kronos_data_raw) {
        $kronos_file = 'https://www.ymcamn.org/sites/default/files/wf_reports/WFC_' . $kronos_file_name_date . '.json';
        $kronos_data_raw = file_get_contents($kronos_file);
      }
      if ($kronos_data_raw) {
        break;
      }
    }

    if (!$kronos_data_raw) {
      $msg = 'Failed to get the data from Kronos file %file.';
      $this->logger->error(
        $msg,
        [
          '%file' => $kronos_file,
        ]
      );
      return FALSE;
    }
    $this->dates['EndDate']  = $kronos_file_name_date;
    $this->dates['StartDate']  = date('Y-m-d', strtotime($kronos_file_name_date . ' -14 days'));
    return json_decode($kronos_data_raw);
  }

  /**
   * Get MB ID by StaffID.
   *
   * @param string $staff_id
   *   Staff ID.
   *
   * @return bool|string
   *   MindBody ID.
   */
  public function getMindbodyidbyStaffId($staff_id) {
    $cache_dir = \Drupal::service('file_system')->realpath(file_default_scheme() . "://");
    $cache_dir = $cache_dir . '/mb_reports';
    $file = $cache_dir . '/staff_ids.json';
    $mb_data_file = file_get_contents($file);
    if ($mb_data_file) {
      //$this->staffIDs = json_decode($mb_data_file, TRUE);
    }
    if (!empty($this->staffIDs[$staff_id])) {
      return $this->staffIDs[$staff_id];
    }
    $staff_params = [
      'PageSize' => 50,
      'CurrentPageIndex' => 0,
      'FunctionName' => 'YMCAGTC_GetEmpID',
      'FunctionParams' => [
        'FunctionParam' => [
          'ParamName' => '@staffID',
          'ParamValue' => $staff_id,
          'ParamDataType' => 'string',
        ],
      ],
    ];
    $staff_id_call = $this->proxy->call('DataService', 'FunctionDataXml', $staff_params, '');
    $mb_staff_id = $staff_id_call->FunctionDataXmlResult->Results;
    if (isset($mb_staff_id->Row->EmpID) && !empty($mb_staff_id->Row->EmpID)) {
      $this->staffIDs[$staff_id] = $mb_staff_id->Row->EmpID;
      return $mb_staff_id->Row->EmpID;
    }
    elseif (isset($staff_id_call->client)) {
      $last_response = $staff_id_call->client->__getLastResponse();
      $encoder = new XmlEncoder();
      $data = $encoder->decode($last_response, 'xml');
      if (isset($data['soap:Body']['FunctionDataXmlResponse']['FunctionDataXmlResult']['Results']['Row']['EmpID'])) {
        $empID = $data['soap:Body']['FunctionDataXmlResponse']['FunctionDataXmlResult']['Results']['Row']['EmpID'];
        $this->staffIDs[$staff_id] = $empID;
        return $empID;
      }
    }
    // @TODO: Log that there is no EmpID.
    return FALSE;
  }

}
