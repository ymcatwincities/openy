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
   * The Reports data.
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
  public function poc() {


    //$this->getMindbodyStaff();
    // @TODO: Get rid from mindbody $this->debug = TRUE.
    // Get Kronos data.
    $trainer_reports = [];
    $location_reports = [];
    // Get locations ref.
    $mapping_repository_location = \Drupal::service('ymca_mappings.location_repository');
    if ($this->getKronosData()) {
      // Calculate Workforce Kronos data.
      foreach ($this->kronosData as $item) {
        if ($item->job == 'PT (one on one and buddy)') {
          $staff_id = $this->getMindbodyidbyStaffId($item->empNo);
          $location_id = $mapping_repository_location->findMindBodyIdByPersonifyId($item->locNo);
          !$location_id && $location_reports[$item->locNo] = 'Location mapping missed.';
          if (!empty($item->totalHours) && !isset($trainer_reports[$location_id][$staff_id]['wf_hours'])) {
            $trainer_reports[$location_id][$staff_id]['wf_hours'] = $item->totalHours;
          }

          $trainer_reports[$location_id][$staff_id]['wf_hours'] += $item->totalHours;
          $trainer_reports[$location_id][$staff_id]['historical_hours'] += $item->historical;
          $trainer_reports[$location_id][$staff_id]['name'] = $item->lastName . ', ' . $item->firstName;
          $location_reports[$location_id]['wf_hours'] += $item->totalHours;
          $location_reports[$location_id]['historical_hours'] += $item->historical;
          $location_reports[$location_id]['name'] = $item->locName;
        }
      }
    }

    if ($this->getMindbodyData()) {
      // Calculate MB data.
      $skip_bt = FALSE;
      $prev_bt = [];
      foreach ($this->mindbodyData as $mb_id => $item) {
        // PT - $item->Program->ID == 2
        // BT - $item->Program->ID == 4.
        $datetime1 = date_create($item->StartDateTime);
        $datetime2 = date_create($item->EndDateTime);

        // Skip every second BT line if time and staff the same.
        if ($item->Program->ID == 4) {
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

        $trainer_reports[$location_id][$staff_id]['mb_hours'] += $diff;
        !empty($item->Staff->LastName) && $trainer_reports[$location_id][$staff_id]['name'] = $item->Staff->LastName . ', ' . $item->Staff->FirstName;
        $location_reports[$location_id]['mb_hours'] += $diff;
        !empty($item->Location->Name) && $location_reports[$location_id]['name'] = $item->Location->Name;
      }
    }
    // Calculate variance.
    foreach ($trainer_reports as $location_id => &$trainers) {
      foreach ($trainers as &$trainer) {
        if (!isset($trainer['mb_hours'])) {
          $trainer['variance'] = '-';
          $trainer['mb_hours'] = '-';
        }
        elseif (!isset($trainer['wf_hours'])) {
          $trainer['variance'] = '-';
          $trainer['wf_hours'] = '-';
        }
        else {
          $trainer['variance'] = round((1 - $trainer['mb_hours'] / $trainer['wf_hours']) * 100);
          $trainer['variance'] .= '%';
        }
        if (!isset($trainer['historical_hours'])) {
          $trainer['historical_hours'] = '-';
        }
      }
    }

    $row['total']['wf_hours'] = $row['total']['mb_hours'] = 0;
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
      $row['total']['wf_hours'] += intval($row['wf_hours']);
      $row['total']['mb_hours'] += intval($row['mb_hours']);
      $row['total']['historical_hours'] += intval($row['historical_hours']);
    }
    $row['total']['wf_hours'] = round($row['total']['wf_hours'], 2);
    $row['total']['mb_hours'] = round($row['total']['mb_hours'], 2);
    $row['total']['historical_hours'] = round($row['total']['historical_hours'], 2);
    if ($row['total']['wf_hours'] == 0) {
      $row['total']['variance'] = '-';
    }
    else {
      $row['total']['variance'] = round((1 - $row['total']['mb_hours'] / $row['total']['wf_hours']) * 100);
      $row['total']['variance'] .= '%';
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
    // New service to add.
    // Saving a file with a path.
    $cache_dir = \Drupal::service('file_system')->realpath(file_default_scheme() . "://");
    $cache_dir = $cache_dir . '/mb_reports';
    $file = $cache_dir . '/WFC_' . $this->dates['EndDate'] . '.json';
    $mb_data_file = file_get_contents($file);
    if (!$mb_data_file) {
      $result = $this->proxy->call('AppointmentService', 'GetStaffAppointments', $params, FALSE);
      $this->mindbodyData = $result->GetStaffAppointmentsResult->Appointments->Appointment;

      if (!file_exists($cache_dir)) {
        mkdir($cache_dir, 0764, TRUE);
      }
      file_put_contents($file, json_encode($this->mindbodyData));
    }
    else {
      $this->mindbodyData = json_decode($mb_data_file);
    }

    if (empty($this->mindbodyData)) {
      $msg = 'Failed to get the data from MindBody. Request params: %params.';
      $this->logger->error(
        $msg,
        [
          '%params' => print_r($params, TRUE),
        ]
      );
    }
    return $this->mindbodyData;
  }

  /**
   * Get MB data.
   *
   * @return bool|string
   *   MindBody ID.
   */
  public function getMindbodyStaff() {
    $user_credentials = [
      'Username' => $this->credentials->get('user_name'),
      'Password' => $this->credentials->get('user_password'),
      'SiteIDs' => [$this->credentials->get('site_id')],
    ];
    $params = [
      'StaffCredentials' => $user_credentials,
      'StaffIDs' => ['100000315'],
    ];

    $result = $this->proxy->call('StaffService', 'GetStaff', $params, FALSE);
    $staff = FALSE;
    isset($result->GetStaffResult->StaffMembers->Staff) && $staff = $result->GetStaffResult->StaffMembers->Staff;
    //$result->GetStaffResult->StaffMembers->Staff->LastName

    return $staff;
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
    $cache_dir = \Drupal::service('file_system')->realpath(file_default_scheme() . "://");
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
      file_put_contents($file, json_encode($this->staffIDs));
      return $mb_staff_id->Row->EmpID;
    }
    elseif (isset($staff_id_call->SoapClient)) {
      $last_response = $staff_id_call->SoapClient->__getLastResponse();
      $encoder = new XmlEncoder();
      $data = $encoder->decode($last_response, 'xml');
      if (isset($data['soap:Body']['FunctionDataXmlResponse']['FunctionDataXmlResult']['Results']['Row']['EmpID'])) {
        $empID = $data['soap:Body']['FunctionDataXmlResponse']['FunctionDataXmlResult']['Results']['Row']['EmpID'];
        $this->staffIDs[$staff_id] = $empID;
        file_put_contents($file, json_encode($this->staffIDs));
        return $empID;
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
  public function sendReports($report_rows = '') {
    //config('yptf_kronos.settings');
    $config = \Drupal::config('yptf_kronos.settings');
    $debug_mode = $config->get('debug');
    $email_type = ['leadership' => 'Leadership email', 'pt_managers' => 'PT managers email'];

    // Get settings.
    $storage = \Drupal::entityTypeManager()->getStorage('mapping');
    $lang = $this->languageManager->getCurrentLanguage()->getId();
    foreach ($email_type as $report_type => $data) {
      if (!empty($config->get($report_type)['enabled']) && !empty($config->get($report_type)['staff_type'])) {
        $recipients = $storage->loadByProperties(['type' => 'staff', 'field_staff_type' => $config->get($report_type)['staff_type']]);
        foreach ($recipients as $index => $recipient) {
          $tokens = $this->createReportTable($recipient, $recipient->field_staff_branch->getValue()[0]['target_id'], $report_type);


          if (!empty($debug_mode)) {
            dpm($tokens);
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


    /*
    // ==========
    // Prepare render array with data.
    $table = [
      '#type' => 'table',
      '#attributes' => array(),
      '#header' => [
        t('*Trainer Name*'),
        t('*Workforce* PT <br>Hours Submitted'),
        t('*MINDBODY* <br>Reported PT Hours<Booked>'),
        t('*Variance*'),
        t('*Historical Hours*<br>Corrected time from past report'),
      ],
      '#rows' => $report_rows,
    ];
    $rendered_data = $this->renderer->renderRoot($table)->__toString();

    $tokens = [
      'data' => $rendered_data,
      'last_run_date' => date('m/d/Y', $last_run_time),
    ];
    try {
      // Send notifications.
      if (!empty($to = $this->configFactory->get('ymca_mindbody.settings')->get('failed_orders_recipients'))) {
        $lang = $this->languageManager->getCurrentLanguage()->getId();
        $this->mailManager->mail('ymca_mindbody', 'yptf_kronos', $to, $lang, $tokens);
      }
    }
    catch (\Exception $e) {
      $msg = 'Failed to send email notification. Error: %error';
      $this->logger->critical($msg, ['%error' => $e->getMessage()]);
    }
    */
  }

  /**
   * @param $recipient
   * @param int $location_id
   *   Location ID.
   * @param string $type
   * @return bool|string
   */

  public function createReportTable($recipient, $location_id, $type = 'leadership') {
    $report_type_name = $type != 'leadership' ? 'Trainer Name' : 'Branch Name';
    $style = '
              <style type="text/css">
                .yptf-kr-table{border-collapse:collapse;border-spacing:0;}
                .yptf-kr-table td{padding:10px5px;border-style:solid;border-width:1px;border-color:#ffffff;overflow:hidden;word-break:normal;}
                .yptf-kr-table th{padding:10px5px;border-style:solid;border-width:1px;border-color:#ffffff;overflow:hidden;word-break:normal;}
                .yptf-kr-table .yptf-kr-purple{background-color:#834cad;color:#ffffff;}
                .yptf-kr-table .yptf-kr-lp{background-color:#e7d7f3;}
              </style>
             ';
    $table = $style . '<table class="yptf-kr-table">
                <tr>
                    <th rowspan="2" class="yptf-kr-white"><strong>' . $report_type_name . '</strong></th>
                    <th colspan="4" class="yptf-kr-purple"><strong>Personal Training Service Hours</strong></th>
                </tr>
                <tr>
                    <td class="yptf-kr-lp"><strong>Workforce</strong> PT <br>Hours Submitted</td>
                    <td class="yptf-kr-lp"><strong>MINDBODY</strong> <br>Reported PT Hours<br>Booked</td>
                    <td class="yptf-kr-lp"><strong>Variance</strong></td>
                    <td class="yptf-kr-lp"><strong>Historical Hours</strong><br>Corrected time<br>from past report</td>
                </tr>               
                ';

    // GEt locations ref.
    $location_repository = \Drupal::service('ymca_mappings.location_repository');
    $location = $location_repository->findByLocationId($location_id);
    $location = is_array($location) ? reset($location) : $location;
    if ($location) {
      $location_mid = $location->field_mindbody_id->getValue()[0]['value'];
    }
    switch ($type) {
      case "pt_managers":
        if (empty($this->reports['trainers'][$location_mid])) {
          return FALSE;
        }
        foreach ($this->reports['trainers'][$location_mid] as $trainer_id => $trainer) {
          $table .= '<tr>
                        <td class="yptf-kr-white">' . $trainer['name'] . '</td>
                        <td class="yptf-kr-lp">' . $trainer['wf_hours'] . '</td>
                        <td class="yptf-kr-lp">' . $trainer['mb_hours'] . '</td>
                        <td class="yptf-kr-lp">' . $trainer['variance'] . '</td>
                        <td class="yptf-kr-lp">' . $trainer['historical_hours'] . '</td>
                     </tr>';
        }
        $summary = $this->reports['locations'][$location_mid];
        $summary['name'] = 'BRANCH TOTAL';
        break;

      case "leadership":
        if (empty($this->reports['trainers'][$location_mid])) {
          return FALSE;
        }
        foreach ($this->reports['locations'] as $loc_id => $branch) {
          $table .= '<tr>
                        <td class="yptf-kr-white">' . $branch['name'] . '</td>
                        <td class="yptf-kr-lp">' . $branch['wf_hours'] . '</td>
                        <td class="yptf-kr-lp">' . $branch['mb_hours'] . '</td>
                        <td class="yptf-kr-lp">' . $branch['variance'] . '</td>
                        <td class="yptf-kr-lp">' . $branch['historical_hours'] . '</td>
                     </tr>';
        }
        $summary = $branch['total'];
        $summary['name'] = 'ALL BRANCHES';
        break;
    }
    $table .= '<tr>
                   <td class="yptf-kr-white"><strong>' . $summary['name'] . '</strong></td>
                   <td class="yptf-kr-lp"><strong>' . $summary['wf_hours'] . '</strong></td>
                   <td class="yptf-kr-lp"><strong>' . $summary['mb_hours'] . '</strong></td>
                   <td class="yptf-kr-lp"><strong>' . $summary['variance'] . '</strong></td>
                   <td class="yptf-kr-lp"><strong>' . $summary['historical_hours'] . '</td>
               </tr>';
    $table .= '</table>';
    return $table;
  }

}
