<?php

namespace Drupal\yptf_kronos;

use Drupal\mindbody_cache_proxy\MindbodyCacheProxy;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Logger\LoggerChannelInterface;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Core\Render\Renderer;
use Drupal\Core\Language\LanguageManagerInterface;

/**
 * Class YptfKronosReports.
 *
 * @package Drupal\yptf_kronos
 */
class YptfKronosReports {

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
   *
   * @var array
   */
  protected $dates;


  /**
   * The Kronos data report.
   *
   * @var array
   */
  protected $kronosData;

  /**
   * The MindBody data report.
   *
   * @var array
   */
  protected $mindbodyData;

  /**
   * The StaffIDs.
   *
   * @var array
   */
  protected $staffIDs;

  /**
   * The Reports data.
   *
   * @var array
   */
  protected $reports;

  /**
   * Mail manager.
   *
   * @var MailManagerInterface
   */
  protected $mailManager;

  /**
   * Renderer.
   *
   * @var \Drupal\Core\Render\Renderer
   */
  protected $renderer;

  /**
   * Language manager.
   *
   * @var LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * ID of BT program which duplicated in MB response.
   *
   * @var int
   */
  protected $programmBTID = 4;

  /**
   * Report date of Kronos file.
   *
   * @var string
   */
  protected $kronosReportDay = 'last Saturday';

  /**
   * Report shift date of Kronos file.
   *
   * @var array
   */
  protected $kronosReportShiftDays = [0, -7];

  /**
   * Kronos file url.
   */
  const KRONOS_FILE_URL_PATTERN = 'https://www.ymcamn.org/sites/default/files/wf_reports/WFC_';

  /**
   * Kronos Training program.
   */
  const KRONOS_TRAINING_ID = 'PT (one on one and buddy)';

  /**
   * YmcaMindbodyExamples constructor.
   *
   * @param ConfigFactory $config_factory
   *   The Config Factory.
   * @param MindbodyCacheProxy $proxy
   *   Cache proxy.
   * @param LoggerChannelInterface $logger
   *   Logger factory.
   * @param MailManagerInterface $mail_manager
   *   Mail manager.
   * @param Renderer $renderer
   *   The renderer.
   * @param LanguageManagerInterface $language_manager
   *   Language manager.
   */
  public function __construct(
    ConfigFactory $config_factory,
    MindbodyCacheProxy $proxy,
    LoggerChannelInterface $logger,
    MailManagerInterface $mail_manager,
    Renderer $renderer,
    LanguageManagerInterface $language_manager
  ) {
    $this->configFactory = $config_factory;
    $this->proxy = $proxy;
    $this->logger = $logger;
    $this->credentials = $this->configFactory->get('mindbody.settings');
    $this->mailManager = $mail_manager;
    $this->renderer = $renderer;
    $this->languageManager = $language_manager;
  }

  /**
   * Calculate/compare data from Kronos & reports.
   */
  public function generateReports() {
    // Get Kronos data.
    $trainer_reports = [];
    $location_reports = [];
    // Get locations ref.
    $mapping_repository_location = \Drupal::service('ymca_mappings.location_repository');
    if ($this->getKronosData()) {
      // Calculate Workforce Kronos data.
      foreach ($this->kronosData as $item) {
        if ($item->job == self::KRONOS_TRAINING_ID) {
          $location_id = $mapping_repository_location->findMindBodyIdByPersonifyId($item->locNo);
          !$location_id && $location_reports[$item->locNo] = 'Location mapping missed.';
          $location_reports[$location_id]['name'] = $item->locName;
          $empID = $this->getMindbodyidbyStaffId($item->empNo);

          if (!$empID) {
            $empID = 'No EmpID for:' . $item->empNo . '. Location:' . $item->locName;
            $trainer_reports[$location_id][$empID]['name'] = $item->lastName . ', ' . $item->firstName . '. * - ' . 'No EmpID for:' . $item->empNo;
          }
          else {
            $trainer_reports[$location_id][$empID]['name'] = $item->lastName . ', ' . $item->firstName;
          }

          if (isset($item->totalHours)) {
            !isset($trainer_reports[$location_id][$empID]['wf_hours']) && $trainer_reports[$location_id][$empID]['wf_hours'] = 0;
            !isset($location_reports[$location_id]['wf_hours']) && $location_reports[$location_id]['wf_hours'] = 0;
          }

          if (isset($item->historical)) {
            !isset($trainer_reports[$location_id][$empID]['historical_hours']) && $trainer_reports[$location_id][$empID]['historical_hours'] = 0;
            !isset($location_reports[$location_id]['historical_hours']) && $location_reports[$location_id]['historical_hours'] = 0;
          }
          $trainer_reports[$location_id][$empID]['wf_hours'] += $item->totalHours;
          $trainer_reports[$location_id][$empID]['historical_hours'] += $item->historical;
          $location_reports[$location_id]['wf_hours'] += $item->totalHours;
          $location_reports[$location_id]['historical_hours'] += $item->historical;

        }
      }
    }

    if ($this->getMindbodyData()) {
      // Calculate MB data.
      $skip_bt = FALSE;
      $prev_bt = [];
      foreach ($this->mindbodyData as $mb_id => $item) {
        $datetime1 = date_create($item->StartDateTime);
        $datetime2 = date_create($item->EndDateTime);

        // PT - $item->Program->ID == 2
        // BT - $item->Program->ID == 4.
        // Skip every second BT line if time and staff the same.
        if ($item->Program->ID == $this->programmBTID) {
          $current_bt = [
            'staff_id' => $item->Staff->ID,
            'StartDateTime' => $item->StartDateTime,
            'EndDateTime' => $item->EndDateTime,
          ];
          if ($skip_bt) {
            if ($prev_bt == $current_bt) {
              $prev_bt = $current_bt;
              $skip_bt = !$skip_bt;
              continue;
            }
          }
          $prev_bt = $current_bt;
          $skip_bt = !$skip_bt;
        }

        $interval = date_diff($datetime1, $datetime2);
        $hours = (int) $interval->format("%h");
        $minutes = (int) $interval->format("%i");
        // Convert minutes to hours.
        $diff = $hours + $minutes / 60;

        $staff_id = $item->Staff->ID;
        $location_id = $item->Location->ID;
        !isset($trainer_reports[$location_id][$staff_id]['mb_hours']) && $trainer_reports[$location_id][$staff_id]['mb_hours'] = 0;
        $trainer_reports[$location_id][$staff_id]['mb_hours'] += $diff;
        !empty($item->Staff->LastName) && $trainer_reports[$location_id][$staff_id]['name'] = $item->Staff->LastName . ', ' . $item->Staff->FirstName;
        !isset($location_reports[$location_id]['mb_hours']) && $location_reports[$location_id]['mb_hours'] = 0;
        $location_reports[$location_id]['mb_hours'] += $diff;
        !empty($item->Location->Name) && $location_reports[$location_id]['name'] = $item->Location->Name;
      }
    }
    // Calculate variance.
    foreach ($trainer_reports as $location_id => &$trainers) {
      foreach ($trainers as &$trainer) {
        $mb_flag = $wf_flag = TRUE;
        if (!isset($trainer['mb_hours'])) {
          $mb_flag = FALSE;
          $trainer['variance'] = '-';
          $trainer['mb_hours'] = '-';
        }
        else {
          $trainer['mb_hours'] = round($trainer['mb_hours'], 2);
        }
        if (!isset($trainer['wf_hours'])) {
          $wf_flag = FALSE;
          $trainer['variance'] = '-';
          $trainer['wf_hours'] = '-';
        }
        else {
          $trainer['wf_hours'] = round($trainer['wf_hours'], 2);
        }
        if ($mb_flag && $wf_flag) {
          $trainer['variance'] = round((1 - $trainer['mb_hours'] / $trainer['wf_hours']) * 100);
          $trainer['variance'] .= '%';
        }

        if (!isset($trainer['historical_hours'])) {
          $trainer['historical_hours'] = '-';
        }
        else {
          $trainer['historical_hours'] = round($trainer['historical_hours'], 2);
        }
      }
    }

    $loc_total['wf_hours'] = $loc_total['mb_hours'] = $loc_total['historical_hours'] = 0;
    foreach ($location_reports as &$row) {
      if (!isset($row['mb_hours'])) {
        $row['variance'] = '-';
        $row['mb_hours'] = '-';
      }
      elseif (!isset($row['wf_hours'])) {
        $row['variance'] = '-';
        $row['wf_hours'] = '-';
      }
      else {
        $row['mb_hours'] = round($row['mb_hours'], 2);
        $row['wf_hours'] = round($row['wf_hours'], 2);
        $row['variance'] = round((1 - $row['mb_hours'] / $row['wf_hours']) * 100);
        $row['variance'] .= '%';
      }
      isset($row['wf_hours']) && $loc_total['wf_hours'] += intval($row['wf_hours']);
      isset($row['mb_hours']) && $loc_total['mb_hours'] += intval($row['mb_hours']);
      isset($row['historical_hours']) && $loc_total['historical_hours'] += intval($row['historical_hours']);
    }
    $location_reports['total']['wf_hours'] = round($loc_total['wf_hours'], 2);
    $location_reports['total']['mb_hours'] = round($loc_total['mb_hours'], 2);
    if (isset($loc_total['total']['historical_hours'])) {
      $location_reports['total']['historical_hours'] = round($loc_total['total']['historical_hours'], 2);
    }
    else {
      $location_reports['total']['historical_hours'] = 0;
    }
    if ($location_reports['total']['wf_hours'] == 0) {
      $location_reports['total']['variance'] = '-';
    }
    else {
      $location_reports['total']['variance'] = round((1 - $location_reports['total']['mb_hours'] / $location_reports['total']['wf_hours']) * 100);
      $location_reports['total']['variance'] .= '%';
    }

    $this->reports['trainers'] = $trainer_reports;
    $this->reports['locations'] = $location_reports;

    $this->sendReports();
  }

  /**
   * Get Kronos Data from file.
   *
   * @return array|bool
   *   List of trainers hours.
   */
  public function getKronosData() {
    $this->kronosData = FALSE;
    $kronos_report_day = $this->kronosReportDay;
    $kronos_shift_days = $this->kronosReportShiftDays;
    if ($week_day = date("w") < 2) {
      foreach ($kronos_shift_days as &$kronos_shift_day) {
        $kronos_shift_day -= 7;
      }
    }
    $kronos_data_raw = $kronos_file = '';
    foreach ($kronos_shift_days as $shift) {
      $kronos_file_name_date = date('Y-m-d', strtotime($kronos_report_day . $shift . 'days'));
      $kronos_path_to_file = \Drupal::service('file_system')->realpath(file_default_scheme() . "://");
      $kronos_file = $kronos_path_to_file . '/wf_reports/WFC_' . $kronos_file_name_date . '.json';
      file_exists($kronos_file) && $kronos_data_raw = file_get_contents($kronos_file);
      if (empty($kronos_data_raw)) {
        $kronos_file = self::KRONOS_FILE_URL_PATTERN . $kronos_file_name_date . '.json';
        $kronos_data_raw = file_get_contents($kronos_file);
      }
      if (!empty($kronos_data_raw)) {
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
      return $this->kronosData;
    }
    $this->dates['EndDate']  = date('Y-m-d', strtotime($kronos_file_name_date . ' -1 day'));;
    $this->dates['StartDate']  = date('Y-m-d', strtotime($kronos_file_name_date . ' -14 days'));
    return $this->kronosData = json_decode($kronos_data_raw);
  }

  /**
   * Get MB data.
   *
   * @return bool|string
   *   MindBody ID.
   */
  public function getMindbodyData() {
    $this->mindbodyData = FALSE;

    $user_credentials = [
      'Username' => $this->credentials->get('user_name'),
      'Password' => $this->credentials->get('user_password'),
      'SiteIDs' => [$this->credentials->get('site_id')],
    ];
    $params = [
      'StaffCredentials' => $user_credentials,
      'StartDate' => $this->dates['StartDate'],
      'EndDate' => $this->dates['EndDate'],
      // Zero is for all staff.
      'StaffIDs' => [0],
    ];

    // Get MB cache for debug mode.
    $debug_mode = $this->configFactory->get('yptf_kronos.settings')->get('debug');
    if (!empty($debug_mode) && FALSE !== strpos($debug_mode, 'cache')) {
      // New service to add.
      // Saving a file with a path.
      $cache_dir = \Drupal::service('file_system')->realpath(file_default_scheme() . "://");
      $cache_dir = $cache_dir . '/mb_reports';
      $file = $cache_dir . '/MB_' . $this->dates['EndDate'] . '.json';
      $mb_data_file = file_get_contents($file);
      if (!$mb_data_file) {
        $result = $this->proxy->call('AppointmentService', 'GetStaffAppointments', $params, TRUE);
        $this->mindbodyData = $result->GetStaffAppointmentsResult->Appointments->Appointment;

        if (!file_exists($cache_dir)) {
          mkdir($cache_dir, 0764, TRUE);
        }
        file_put_contents($file, json_encode($this->mindbodyData));
      }
      else {
        $this->mindbodyData = json_decode($mb_data_file);
      }
    }

    if (empty($this->mindbodyData)) {
      try {
        // Send notifications.
        $result = $this->proxy->call('AppointmentService', 'GetStaffAppointments', $params, TRUE);
        $this->mindbodyData = $result->GetStaffAppointmentsResult->Appointments->Appointment;
      }
      catch (\Exception $e) {
        $msg = 'Error: %error . Failed to get the data from MindBody. Request MB params: %params.';
        $this->logger->error($msg, [
          '%error' => $e->getMessage(),
          '%params' => print_r($params, TRUE),
        ]);
        return FALSE;
      }

    }

    return $this->mindbodyData;
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
    if (!empty($this->staffIDs[$staff_id])) {
      return $this->staffIDs[$staff_id];
    }
    // Get MB cache for debug mode.
    $debug_mode = $this->configFactory->get('yptf_kronos.settings')->get('debug');
    if (!empty($debug_mode) && FALSE !== strpos($debug_mode, 'cache')) {
      if (!isset($this->staffIDs)) {
        $cache_dir = \Drupal::service('file_system')
          ->realpath(file_default_scheme() . "://");
        $cache_dir = $cache_dir . '/mb_reports';
        if (!file_exists($cache_dir)) {
          mkdir($cache_dir, 0764, TRUE);
        }
        $file = $cache_dir . '/staff_ids.json';
        $mb_data_file = file_get_contents($file);
        if ($mb_data_file) {
          $this->staffIDs = json_decode($mb_data_file, TRUE);
        }
        if (!empty($this->staffIDs[$staff_id])) {
          return $this->staffIDs[$staff_id];
        }
      }
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

    $mb_staff_id = FALSE;
    try {
      // Send notifications.
      $staff_id_call = $this->proxy->call('DataService', 'FunctionDataXml', $staff_params, TRUE);
      $mb_staff_id = $staff_id_call->FunctionDataXmlResult->Results;
    }
    catch (\Exception $e) {
      $msg = 'Error: %error . Failed to get the Employee ID from MindBody. Request MB params: %params.';
      $this->logger->error($msg, [
        '%error' => $e->getMessage(),
        '%params' => $staff_id,
      ]);
    }

    if (isset($mb_staff_id->Row->EmpID) && !empty($mb_staff_id->Row->EmpID)) {
      $this->staffIDs[$staff_id] = $mb_staff_id->Row->EmpID;
      // Get MB cache for debug mode.
      $debug_mode = $this->configFactory->get('yptf_kronos.settings')->get('debug');
      if (!empty($debug_mode) && FALSE !== strpos($debug_mode, 'cache')) {
        file_put_contents($file, json_encode($this->staffIDs));
      }
      return $mb_staff_id->Row->EmpID;
    }
    elseif (isset($staff_id_call->SoapClient)) {
      $last_response = $staff_id_call->SoapClient->__getLastResponse();
      $encoder = new XmlEncoder();
      $data = $encoder->decode($last_response, 'xml');
      $parsed_data = $data['soap:Body']['FunctionDataXmlResponse']['FunctionDataXmlResult'];

      if ($parsed_data['Status'] == 'Success') {
        if ($parsed_data['ResultCount'] == 1) {
          if (isset($parsed_data['Results']['Row']['EmpID'])) {
            $empID = $parsed_data['Results']['Row']['EmpID'];
            $this->staffIDs[$staff_id] = $empID;
            // Get MB cache for debug mode.
            $debug_mode = $this->configFactory->get('yptf_kronos.settings')->get('debug');
            if (!empty($debug_mode) && FALSE !== strpos($debug_mode, 'cache')) {
              file_put_contents($file, json_encode($this->staffIDs));
            }
            return $empID;
          }
        }
        elseif ($parsed_data['ResultCount'] > 1) {
          $msg = 'Multiple Employee ID from MindBody. Staff ID: %params.';
          $this->logger->notice($msg, ['%params' => $staff_id]);
          $parsed_data_first_id = reset($parsed_data['Results']['Row']);
          if (isset($parsed_data_first_id['EmpID'])) {
            $empID = $parsed_data_first_id['EmpID'];
            $this->staffIDs[$staff_id] = $empID;
            // Get MB cache for debug mode.
            $debug_mode = $this->configFactory->get('yptf_kronos.settings')->get('debug');
            if (!empty($debug_mode) && FALSE !== strpos($debug_mode, 'cache')) {
              file_put_contents($file, json_encode($this->staffIDs));
            }
            return $empID;
          }
        }
      }
    }
    if (empty($empID)) {
      $msg = 'Failed to get the Employee ID from MindBody. Staff ID: %params.';
      $this->logger->error($msg, ['%params' => $staff_id]);
    }
    return FALSE;
  }

  /**
   * Send reports.
   */
  public function sendReports() {
    $config = $this->configFactory->get('yptf_kronos.settings');
    $debug_mode = $config->get('debug');
    $email_type = ['leadership' => 'Leadership email', 'pt_managers' => 'PT managers email'];
    $report_tokens = ['leadership' => '[leadership-report]', 'pt_managers' => '[pt-manager-report]'];

    // Get settings.
    $storage = \Drupal::entityTypeManager()->getStorage('mapping');
    $lang = $this->languageManager->getCurrentLanguage()->getId();

    foreach ($email_type as $report_type => $data) {
      if (!empty($config->get($report_type)['enabled']) && !empty($config->get($report_type)['staff_type'])) {
        $recipients = $storage->loadByProperties(['type' => 'staff', 'field_staff_type' => $config->get($report_type)['staff_type']]);
        foreach ($recipients as $index => $recipient) {

          $body = $config->get($report_type)['body']['value'];
          $token = $this->createReportTable($recipient->field_staff_branch->getValue()[0]['target_id'], $report_type);
          if (!$token) {
            $token = 'No data.';
          }
          $tokens['body'] = str_replace($report_tokens[$report_type], $token['report'], $body);
          $tokens['subject'] = $token['name'] . ' ' . $config->get($report_type)['subject'];
          $tokens['subject'] .= ' ' . date("m/d/Y", strtotime($this->dates['StartDate'])) . ' - ' . date("m/d/Y", strtotime($this->dates['EndDate']));
          // Debug Mode: Print results on screen or send to mail.
          if (!empty($debug_mode) && FALSE !== strpos($debug_mode, 'dpm')) {
            print ($tokens['subject']);
            print ($token['report']);
          }
          elseif (!empty($debug_mode) && strpos($debug_mode, 'email')) {
            $debug_email = explode('email', $debug_mode);
            $debug_email = end($debug_email);
            try {
              // Send notifications.
              $this->mailManager->mail('yptf_kronos', 'yptf_kronos_reports', $debug_email, $lang, $tokens);
            }
            catch (\Exception $e) {
              $msg = 'Failed to send email report. Error: %error';
              $this->logger->critical($msg, ['%error' => $e->getMessage()]);
            }
          }
          else {
            try {
              // Send notifications.
              $this->mailManager->mail('yptf_kronos', 'yptf_kronos_reports', $recipient->field_staff_email->getValue()[0]['value'], $lang, $tokens);
            }
            catch (\Exception $e) {
              $msg = 'Failed to send email report. Error: %error';
              $this->logger->critical($msg, ['%error' => $e->getMessage()]);
            }
          }
        }
      }
    }
  }

  /**
   * Render report table.
   *
   * @param int $location_id
   *   Location ID.
   * @param string $type
   *   Email type.
   *
   * @return bool|string
   *   Rendered value.
   */
  public function createReportTable($location_id, $type = 'leadership') {
    $data['report_type_name'] = $type != 'leadership' ? t('Trainer Name') : t('Branch Name');

    // Get locations ref.
    $location_repository = \Drupal::service('ymca_mappings.location_repository');
    $location = $location_repository->findByLocationId($location_id);
    $location = is_array($location) ? reset($location) : $location;
    if ($location) {
      $location_mid = $location->field_mindbody_id->getValue()[0]['value'];
    }
    else {
      $msg = 'No location on site for MB location_id: %params.';
      $this->logger->notice($msg, ['%params' => $location_id]);
      return FALSE;
    }
    switch ($type) {
      case "pt_managers":
        if (empty($this->reports['trainers'][$location_mid])) {
          return FALSE;
        }
        $data['rows'] = $this->reports['trainers'][$location_mid];

        // Sort by names.
        $names = [];
        foreach ($data['rows'] as &$name) {
          $names[] = &$name["name"];
        }
        array_multisort($names, $data['rows']);

        $data['summary'] = $this->reports['locations'][$location_mid];;
        $data['summary']['name'] = t('BRANCH TOTAL');

        $location_name = $location->getName();
        break;

      case "leadership":
        if (empty($this->reports['locations'])) {
          return FALSE;
        }
        $data['summary'] = $this->reports['locations']['total'];
        $data['summary']['name'] = t('ALL BRANCHES');
        unset($this->reports['locations']['total']);
        $data['rows'] = $this->reports['locations'];
        // Sort by names.
        $names = [];
        foreach ($data['rows'] as &$name) {
          $names[] = &$name["name"];
        }
        array_multisort($names, $data['rows']);
        $location_name = '';
        break;
    }

    $variables = [
      '#theme' => 'yptf_kronos_report',
      '#data' => $data,
    ];

    $table = render($variables);
    return ['report' => $table, 'name' => $location_name];
  }

}
