<?php

namespace Drupal\yptf_kronos;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Core\Render\Renderer;
use Drupal\mindbody_cache_proxy\MindbodyCacheProxy;
use Symfony\Component\Serializer\Encoder\XmlEncoder;

/**
 * Class YptfKronosReports.
 *
 * @package Drupal\yptf_kronos
 */
class YptfKronosReports extends YptfKronosReportsBase implements YptfKronosReportsInterface {

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
   * IDs of programs in MB response.
   *
   * @var array
   */
  protected $programIDs = ['PT' => 2, 'BT' => 4];

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
   *
   * @var array
   */
  protected $kronosTrainingId = [
    'PT (small groups)',
    'PT (group of 5 or more)',
  ];

  /**
   * Flag for report calculation.
   *
   * @var bool
   */
  protected $reportCalculated = FALSE;

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
  public function __construct(ConfigFactory $config_factory, MindbodyCacheProxy $proxy, LoggerChannelInterface $logger, MailManagerInterface $mail_manager, Renderer $renderer, LanguageManagerInterface $language_manager) {
    $this->configFactory = $config_factory;
    $this->proxy = $proxy;
    $this->logger = $logger;
    $this->credentials = $this->configFactory->get('mindbody.settings');
    $this->mailManager = $mail_manager;
    $this->renderer = $renderer;
    $this->languageManager = $language_manager;
  }

  /**
   * Generate reports.
   */
  public function generateReports() {
    $this->logger->info('Kronos Tuesday email reports generator started.');
    $this->getInitialDates();
    $this->sendReports();
    $this->sendErrorReports();
    $this->logger->info('Kronos Tuesday email reports generator finished.');
  }

  /**
   * Calculate/compare data from Kronos & reports.
   */
  public function calculateReports() {
    // Report calculated flag.
    $this->reportCalculated = TRUE;

    // Get Kronos data.
    $trainer_reports = [];
    $location_reports = [];

    // Get locations ref.
    $mapping_repository_location = \Drupal::service('ymca_mappings.location_repository');

    if ($this->getKronosData()) {
      // Calculate Workforce Kronos data.
      foreach ($this->kronosData as $current_line => $item) {
        if (in_array($item->job, $this->kronosTrainingId)) {
          $location_id = $mapping_repository_location->findMindBodyIdByPersonifyId($item->locNo);
          if (!$location_id) {
            throw new \Exception(sprintf('Failed to get MindBody Location ID by Personify ID: %d', $item->locNo));
          }

          $mapping = $mapping_repository_location->findByMindBodyId($location_id);
          $loc_name = $mapping->field_location_name->value;

          $location_reports[$location_id]['name'] = $loc_name;
          $empID = $item->empNo;

          if (empty($empID)) {
            // No EmpID.
            $empID = 'KWFC - No Staff ID for: ' . $item->lastName . ', ' . $item->firstName;
            $trainer_reports[$location_id][$empID]['name'] = $item->lastName . ', ' . $item->firstName . '. * - ' . $empID;
            $this->addError('Kronos Data', sprintf('No EmpID for %s, %s in %s has been found.', $item->lastName, $item->firstName, $item->locName));
          }
          elseif (!is_array($empID) && !isset($trainer_reports[$location_id][$empID]['name'])) {
            $trainer_reports[$location_id][$empID]['name'] = $item->lastName . ', ' . $item->firstName;
          }

          if (isset($item->totalHours)) {
            // Skip trainer calculation for multiple empIDs.
            !is_array($empID) && !isset($trainer_reports[$location_id][$empID]['wf_hours']) ? $trainer_reports[$location_id][$empID]['wf_hours'] = 0 : '';
            !isset($location_reports[$location_id]['wf_hours']) && $location_reports[$location_id]['wf_hours'] = 0;
          }

          if (isset($item->historical)) {
            // Skip trainer calculation for multiple empIDs.
            !is_array($empID) && !isset($trainer_reports[$location_id][$empID]['historical_hours']) ? $trainer_reports[$location_id][$empID]['historical_hours'] = 0 : '';
            !isset($location_reports[$location_id]['historical_hours']) ? $location_reports[$location_id]['historical_hours'] = 0 : '';
          }

          // Skip trainer calculation for multiple empIDs.
          if (!is_array($empID)) {
            $trainer_reports[$location_id][$empID]['wf_hours'] += $item->totalHours;
            $trainer_reports[$location_id][$empID]['historical_hours'] += $item->historical;
          }

          $location_reports[$location_id]['wf_hours'] += $item->totalHours;
          $location_reports[$location_id]['historical_hours'] += $item->historical;
        }
      }
    }

    if ($this->getMindBodyCSVData($this->kronosData)) {
      // Calculate MB data.
      foreach ($this->mindbodyData as $mb_id => $item) {
        $diff = $item['HoursBooked'];

        $staff_id = isset($item['EmpID']) ? $item['EmpID'] : NULL;
        $name = explode(' ', $item['Staff']);
        if (is_array($name)) {
          $firstName = $name[0];
          $lastName = end($name);
        }
        else {
          $firstName = $lastName = $item['Staff'];
        }
        $trainer_name = $lastName . ', ' . $firstName;
        if (empty($staff_id)) {
          // No EmpID.
          $staff_id = 'Discrepancy: ' . $item['Staff'];
          $trainer_name .= ' (discrepancy)';
          $this->addError('Discrepancy', sprintf('No corresponding name for "%s" (%s) has been found in Kronos data.', $item['Staff'], $item['Location']));
        }

        // Get MB location ID.
        $brCode = $this->getBranchCodeIdByMBLocationName($item['Location']);
        if (!$brCode) {
          throw new \Exception(sprintf('Failed to get Personify Location ID by MB Location Name: %s', $item['Location']));
        }
        $location_id = $mapping_repository_location->findMindBodyIdByPersonifyId($brCode);
        if (!$location_id) {
          throw new \Exception(sprintf('Failed to get MindBody Location ID by Personify ID: %d', $item->locNo));
        }

        !isset($trainer_reports[$location_id][$staff_id]['mb_hours']) ? $trainer_reports[$location_id][$staff_id]['mb_hours'] = 0 : '';
        $trainer_reports[$location_id][$staff_id]['mb_hours'] += (float) $diff;
        !empty($trainer_name) ? $trainer_reports[$location_id][$staff_id]['name'] = $trainer_name : '';

        !isset($location_reports[$location_id]['mb_hours']) ? $location_reports[$location_id]['mb_hours'] = 0 : '';
        $location_reports[$location_id]['mb_hours'] += (float) $diff;
        !empty($item['Location']) ? $location_reports[$location_id]['name'] = $item['Location'] : '';
      }
    }

    // Suppress data for specific locations.
    // Dayton at Gaviidae - DT Minneapolis - (MB ID: 5).
    // https://www.ymcamn.org/admin/content/locations-mapping
    unset($location_reports[5]);
    unset($trainer_reports[5]);

    // Calculate variance for trainers.
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
          // We can't divide by zero, setting variance to be "-" in that case.
          if ($trainer['wf_hours'] != 0)  {
            $trainer['variance'] = round(
              (1 - $trainer['mb_hours'] / $trainer['wf_hours']) * 100
            );
            $trainer['variance'] .= '%';
          }
          else {
            $trainer['variance'] = '-';
          }
        }

        if (!isset($trainer['historical_hours'])) {
          $trainer['historical_hours'] = '-';
        }
        else {
          $trainer['historical_hours'] = round($trainer['historical_hours'], 2);
        }
      }
    }

    // Calculate variance for locations.
    $loc_total['wf_hours'] = $loc_total['mb_hours'] = $loc_total['historical_hours'] = 0;
    foreach ($location_reports as &$row) {
      if (!is_array($row)) {
        continue;
      }
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
        $row['variance'] = round(
          (1 - $row['mb_hours'] / $row['wf_hours']) * 100
        );
        $row['variance'] .= '%';
      }
      isset($row['wf_hours']) ? $loc_total['wf_hours'] += intval(
        $row['wf_hours']
      ) : '';
      isset($row['mb_hours']) ? $loc_total['mb_hours'] += intval(
        $row['mb_hours']
      ) : '';
      isset($row['historical_hours']) ? $loc_total['historical_hours'] += intval(
        $row['historical_hours']
      ) : '';
    }
    $location_reports['total']['wf_hours'] = round($loc_total['wf_hours'], 2);
    $location_reports['total']['mb_hours'] = round($loc_total['mb_hours'], 2);
    if (isset($loc_total['total']['historical_hours'])) {
      $location_reports['total']['historical_hours'] = round(
        $loc_total['total']['historical_hours'],
        2
      );
    }
    else {
      $location_reports['total']['historical_hours'] = 0;
    }
    if ($location_reports['total']['wf_hours'] == 0) {
      $location_reports['total']['variance'] = '-';
    }
    else {
      $location_reports['total']['variance'] = round(
        (1 - $location_reports['total']['mb_hours'] / $location_reports['total']['wf_hours']) * 100
      );
      $location_reports['total']['variance'] .= '%';
    }

    $this->reports['trainers'] = $trainer_reports;
    $this->reports['locations'] = $location_reports;
  }

  /**
   * Get initial Dates.
   */
  public function getInitialDates() {
    $kronos_report_day = $this->kronosReportDay;
    $kronos_shift_days = $this->kronosReportShiftDays;
    if ($week_day = date("w") < 2) {
      foreach ($kronos_shift_days as &$kronos_shift_day) {
        $kronos_shift_day -= 7;
      }
    }
    $this->dates['EndDate'] = date('Y-m-d', strtotime($kronos_report_day));
    $this->dates['StartDate'] = date(
      'Y-m-d',
      strtotime($kronos_report_day . ' -13 days')
    );
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
    $kronos_file_name_date = date('Y-m-d', strtotime($kronos_report_day));
    $kronos_data_raw = $kronos_file = '';
    foreach ($kronos_shift_days as $shift) {
      $kronos_file_name_date = date(
        'Y-m-d',
        strtotime($kronos_report_day . $shift . 'days')
      );
      $kronos_path_to_file = \Drupal::service('file_system')->realpath(
        file_default_scheme() . "://"
      );
      $kronos_file = $kronos_path_to_file . '/wf_reports/WFC_' . $kronos_file_name_date . '.json';
      file_exists($kronos_file) ? $kronos_data_raw = file_get_contents(
        $kronos_file
      ) : '';
      if (empty($kronos_data_raw)) {
        $kronos_file = self::KRONOS_FILE_URL_PATTERN . $kronos_file_name_date . '.json';
        $kronos_data_raw = @file_get_contents($kronos_file);
      }
      if (!empty($kronos_data_raw)) {
        break;
      }
    }

    if (!$kronos_data_raw) {
      $msg = 'Failed to get the data from Kronos file %file.';
      $this->logger->notice($msg, ['%file' => $kronos_file]);
      $kronos_file_name_date2 = date(
        'Y-m-d',
        strtotime($kronos_report_day . reset($kronos_shift_days) . 'days')
      );
      $this->reports['messages']['error_reports']['No Kronos file for two weeks:'][] = t(
        'Failed to get the data from Kronos file %file1 and %file2. Contact the FFW team.',
        [
          '%file1' => $kronos_file,
          '%file2' => self::KRONOS_FILE_URL_PATTERN . $kronos_file_name_date2 . '.json',
        ]
      );
      return $this->kronosData;
    }
    $this->dates['EndDate'] = date('Y-m-d', strtotime($kronos_file_name_date));
    $this->dates['StartDate'] = date(
      'Y-m-d',
      strtotime($kronos_file_name_date . ' -13 days')
    );
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

    // Calculate dates for the MB request.
    $start_time_report = strtotime($this->dates['StartDate']);
    if (empty($this->dates['mbEndDate'])) {
      $this->dates['mbEndDate'] = $this->dates['EndDate'];
      $start_time_calc = strtotime(
        $this->dates['mbEndDate'] . ' -' . ceil(
          14 / $this->numberOfRequest
        ) . ' days'
      );
      if ($start_time_calc > $start_time_report) {
        $this->dates['mbStartDate'] = date('Y-m-d', $start_time_calc);
      }
      else {
        $this->dates['mbStartDate'] = $this->dates['StartDate'];
      }
    }
    else {
      if (empty($this->dates['mbStartDate'])) {
        return FALSE;
      }
      $this->dates['mbEndDate'] = date(
        'Y-m-d',
        strtotime($this->dates['mbStartDate'] . ' -1 day')
      );
      if (strtotime($this->dates['mbEndDate']) < $start_time_report) {
        return FALSE;
      }
      $start_time_calc = strtotime(
        $this->dates['mbEndDate'] . ' -' . ceil(
          14 / $this->numberOfRequest
        ) . ' days'
      );
      if ($start_time_calc > $start_time_report) {
        $this->dates['mbStartDate'] = date('Y-m-d', $start_time_calc);
      }
      else {
        $this->dates['mbStartDate'] = $this->dates['StartDate'];
      }
    }

    $params = [
      'PageSize' => 50,
      'CurrentPageIndex' => 0,
      'FunctionName' => 'YMCAGTC_ApptMetrics',
      'FunctionParams' => [
        [
          'ParamName' => '@startDate',
          'ParamValue' => $this->dates['mbStartDate'],
          'ParamDataType' => 'string',
        ],
        [
          'ParamName' => '@endDate',
          'ParamValue' => $this->dates['mbEndDate'],
          'ParamDataType' => 'string',
        ],
      ],
    ];

    // Get MB cache for debug mode.
    $debug_mode = $this->configFactory->get('yptf_kronos.settings')->get(
      'debug'
    );

    // Never use cache files!
    if (FALSE && !empty($debug_mode) && FALSE !== strpos($debug_mode, 'cache')) {
      // New service to add.
      // Saving a file with a path.
      $cache_dir = \Drupal::service('file_system')->realpath(
        file_default_scheme() . "://"
      );
      $cache_dir = $cache_dir . '/mb_reports';
      $file = $cache_dir . '/MB_' . $this->dates['EndDate'] . '.json';
      $mb_data_file = file_get_contents($file);
      if (!$mb_data_file) {
        $result = $this->proxy->call(
          'DataService',
          'FunctionDataXml',
          $params,
          FALSE
        );
        $this->mindbodyData = $result->FunctionDataXmlResult->Results;

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
        $result = $this->proxy->call(
          'DataService',
          'FunctionDataXml',
          $params,
          FALSE
        );
        $this->mindbodyData = $result->FunctionDataXmlResult->Results;
      } catch (\Exception $e) {
        $msg = 'Error: %error . Failed to get the data from MindBody. Request MB params: %params.';
        $this->logger->notice(
          $msg,
          [
            '%error' => $e->getMessage(),
            '%params' => print_r($params, TRUE),
          ]
        );
        $this->reports['messages']['error_reports']['Failed request for MB report data:'][] = t(
          'Error: %error . Failed to get the data from MindBody. Request MB params: %params. Contact the MindBody team.',
          [
            '%error' => $e->getMessage(),
            '%params' => print_r($params, TRUE),
          ]
        );
        return FALSE;
      }

    }

    if (isset($result->Row) && !empty($result->Row) && $first_row = reset(
          $result->Row
        ) && !empty($first_row)) {
      $this->mindbodyData = $result->Row;
    }
    elseif (isset($result->SoapClient)) {
      try {
        $last_response = $result->SoapClient->__getLastResponse();
        $encoder = new XmlEncoder();
        $data = $encoder->decode($last_response, 'xml');
        $parsed_data = $data['soap:Body']['FunctionDataXmlResponse']['FunctionDataXmlResult'];
      } catch (\Exception $e) {
        $msg = 'Error: %error . Failed to get SoapClient->lastResponse from MindBody request for: %params.';
        $this->logger->notice(
          $msg,
          [
            '%error' => $e->getMessage(),
            '%params' => $params,
          ]
        );
      }

      if (isset($parsed_data['Status']) && $parsed_data['Status'] == 'Success') {
        if (isset($parsed_data['Results']['Row'])) {
          $this->mindbodyData = $parsed_data['Results']['Row'];
        }
        else {
          $msg = 'Failed to get data from SoapClient->lastResponse from MindBody request for: %params.';
          $this->logger->notice(
            $msg,
            [
              '%params' => $params,
            ]
          );
          return FALSE;
        }
      }
      else {
        $msg = 'Failed to get data from SoapClient->lastResponse from MindBody request for: %params.';
        $this->logger->notice(
          $msg,
          [
            '%params' => $params,
          ]
        );
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

    $mb_staff_id = $mb_server_fails = FALSE;
    try {
      // Send notifications.
      $staff_id_call = $this->proxy->call(
        'DataService',
        'FunctionDataXml',
        $staff_params,
        TRUE
      );
      $mb_staff_id = $staff_id_call->FunctionDataXmlResult->Results;
    } catch (\Exception $e) {
      $mb_server_fails = TRUE;
      $msg = 'Error: %error . Failed to get the Employee ID from MindBody. Request MB params: %params.';
      $this->logger->notice(
        $msg,
        [
          '%error' => $e->getMessage(),
          '%params' => $staff_id,
        ]
      );
    }

    if (isset($mb_staff_id->Row->EmpID) && !empty($mb_staff_id->Row->EmpID)) {
      $this->staffIDs[$staff_id] = $mb_staff_id->Row->EmpID;
      return $mb_staff_id->Row->EmpID;
    }
    elseif (isset($staff_id_call->SoapClient)) {

      try {
        $last_response = $staff_id_call->SoapClient->__getLastResponse();
        $encoder = new XmlEncoder();
        $data = $encoder->decode($last_response, 'xml');
        $parsed_data = $data['soap:Body']['FunctionDataXmlResponse']['FunctionDataXmlResult'];
      } catch (\Exception $e) {
        $msg = 'Error: %error . Failed to get SoapClient->lastResponse from MindBody request for staffID: %params.';
        $this->logger->notice(
          $msg,
          [
            '%error' => $e->getMessage(),
            '%params' => $staff_id,
          ]
        );
      }

      if (isset($parsed_data['Status']) && $parsed_data['Status'] == 'Success') {
        if ($parsed_data['ResultCount'] == 1) {
          if (isset($parsed_data['Results']['Row']['EmpID'])) {
            $empID = $parsed_data['Results']['Row']['EmpID'];
            $this->staffIDs[$staff_id] = $empID;
            return $empID;
          }
        }
        elseif ($parsed_data['ResultCount'] > 1) {
          $msg = 'Multiple Employee ID from MindBody. Staff ID: %params.';
          $this->logger->notice($msg, ['%params' => $staff_id]);
          if (isset($parsed_data['Results']['Row'])) {
            $empID = $parsed_data['Results']['Row'];
            return $empID;
          }
        }
      }
    }
    if (empty($empID)) {
      $msg = 'Failed to get the Employee ID from MindBody. Staff ID: %params.';
      $this->logger->notice($msg, ['%params' => $staff_id]);
      if ($mb_server_fails) {
        return 0;
      }
    }
    return FALSE;
  }

  /**
   * Send reports.
   */
  public function sendReports() {
    $config = $this->configFactory->get('yptf_kronos.settings');
    $debug_mode = $config->get('debug');
    $email_type = [
      'leadership' => 'Leadership email',
      'pt_managers' => 'PT managers email',
    ];
    $report_tokens = [
      'leadership' => '[leadership-report]',
      'pt_managers' => '[pt-manager-report]',
    ];

    // Get settings.
    $storage = \Drupal::entityTypeManager()->getStorage('mapping');
    $lang = $this->languageManager->getCurrentLanguage()->getId();

    foreach ($email_type as $report_type => $data) {
      $enabled_setting = !empty($config->get($report_type)['enabled']) ? $config->get($report_type)['enabled'] : FALSE;
      $enabled_condition = trim(
        strip_tags(
          str_replace(
            '&nbsp;',
            '',
            $config->get($report_type)['disabled_message']['value']
          )
        )
      );
      $enabled_condition = $enabled_setting || !empty($enabled_condition);
      if ($enabled_condition && !empty($config->get($report_type)['staff_type'])) {
        $recipients = $storage->loadByProperties(
          [
            'type' => 'staff',
            'field_staff_type' => $config->get($report_type)['staff_type'],
          ]
        );
        foreach ($recipients as $index => $recipient) {
          if ($enabled_setting && !$this->reportCalculated) {
            $this->calculateReports();
          }
          $body = $enabled_setting ? $config->get($report_type)['body']['value'] : $config->get($report_type)['disabled_message']['value'];
          $token = FALSE;

          if ($report_type == 'leadership') {
            $token = $this->createReportTable('', $report_type, $enabled_setting);
          }
          elseif(!empty($recipient->field_staff_branch->getValue()[0]['target_id'])) {
            $token = $this->createReportTable($recipient->field_staff_branch->getValue()[0]['target_id'], $report_type, $enabled_setting);
          }
          else {
            $msg = 'PT Manager "%surname, %name" has no branch.';
            $this->logger->notice(
              $msg,
              [
                '%surname' => $recipient->field_staff_surname->getValue()[0]['value'],
                '%name' => $recipient->field_staff_name->getValue()[0]['value'],
              ]
            );
          }
          if (!$token) {
            continue;
          }

          $tokens['body'] = $enabled_setting ? str_replace($report_tokens[$report_type], $token['report'], $body) : $body;
          $tokens['subject'] = str_replace('[report-branch-name]', $token['name'], $config->get($report_type)['subject']);
          $tokens['subject'] = str_replace('[report-start-date]', date("d/m/Y", strtotime($this->dates['StartDate'])), $tokens['subject']);
          $tokens['subject'] = str_replace('[report-end-date]', date("d/m/Y", strtotime($this->dates['EndDate'])), $tokens['subject']);

          // Debug Mode: Print results on screen or send to mail.
          if (FALSE && !empty($debug_mode) && FALSE !== strpos($debug_mode, 'dpm')) {
            print ($tokens['subject']);
            print ($token['report']);
          }
          elseif (FALSE && !empty($debug_mode) && strpos($debug_mode, 'email')) {
            $debug_email = explode('email', $debug_mode);
            $debug_email = end($debug_email);

            // Check whether we need to send the emails.
            if ($this->hasErrors() && $this->sendWithErrors == FALSE) {
              continue;
            }

            try {
              // Send notifications.
              $this->mailManager->mail(
                'yptf_kronos',
                'yptf_kronos_reports',
                $debug_email,
                $lang,
                $tokens
              );
            } catch (\Exception $e) {
              $msg = 'Failed to send email report. Error: %error';
              $this->logger->notice($msg, ['%error' => $e->getMessage()]);
            }
          }
          else {
            // Check whether we need to send the emails.
            if ($this->hasErrors() && $this->sendWithErrors == FALSE) {
              continue;
            }

            try {
              // Send notifications.
              $this->mailManager->mail(
                'yptf_kronos',
                'yptf_kronos_reports',
                $recipient->field_staff_email->getValue()[0]['value'],
                $lang,
                $tokens
              );
            } catch (\Exception $e) {
              $msg = 'Failed to send email report. Error: %error';
              $this->logger->notice($msg, ['%error' => $e->getMessage()]);
              $this->reports['messages']['error_reports']['Failed to send email. Email server issue:'][] = t(
                'Failed to send email report. Error: %error . Contact the FFW team.',
                [
                  '%error' => print_r($e->getMessage(), TRUE),
                ]
              );
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
   * @param bool $enabled_setting
   *   Setting of/off.
   *
   * @return mixed
   *   Rendered value.
   */
  public function createReportTable($location_id, $type = 'leadership', $enabled_setting = TRUE) {
    $data['report_type_name'] = $type != 'leadership' ? t('Trainer Name') : t('Branch Name');

    switch ($type) {
      case "pt_managers":
        if (empty($location_id)) {
          return FALSE;
        }

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
          $this->reports['messages']['error_reports']['No location mapping based on MB location ID:'][] = t(
            'No location on site for MB location_id: %params. Contact the FFW team.',
            [
              '%params' => print_r($location_id, TRUE),
            ]
          );
          return FALSE;
        }
        $location_name = $location->getName();
        $table = '';
        if ($enabled_setting) {
          if (empty($this->reports['trainers'][$location_mid])) {
            return FALSE;
          }
          $data['rows'] = $this->reports['trainers'][$location_mid];

          // Sort by names.
          $names = [];
          foreach ($data['rows'] as &$name) {
            $names[] = &$name["name"];
          }
          natcasesort($names);
          array_multisort($names, $data['rows']);

          $data['summary'] = $this->reports['locations'][$location_mid];;
          $data['summary']['name'] = t('BRANCH TOTAL');
          $data['messages'] = '';
          if (isset($this->reports['messages']['multi_ids'][$location_mid])) {
            $data['messages'] = $this->reports['messages']['multi_ids'][$location_mid];
            $admin_emails = $config = $this->configFactory->get(
              'yptf_kronos.settings'
            )
              ->get('admin_emails');
            $data['admin_mail_raw'] = '';
            $data['admin_mail'] = '';
            if (!empty($admin_emails)) {
              $admin_emails = explode(',', $admin_emails);
              foreach ($admin_emails as $index => $email) {
                $email = trim($email);
                if (empty($email)) {
                  continue;
                }

                $data['admin_mail_raw']["@admin_mail$index"] = $email;
                $data['admin_mail'] .= "<a href='mailto:@admin_mail$index' target='_top'>@admin_mail$index</a> ";
              }
            }
            if (!empty($data['admin_mail_raw'])) {
              $data['admin_mail'] = new FormattableMarkup(
                $data['admin_mail'],
                $data['admin_mail_raw']
              );
            }
            else {
              $data['admin_mail'] = 'YMCA Team';
            }
          }
          $variables = [
            '#theme' => 'yptf_kronos_report',
            '#data' => $data,
          ];
          // Drush can't render that 'render($variables);' cause it has miss
          // context instead use command below.
          $table = $this->renderer->renderRoot($variables);
        }

        break;

      case "leadership":
        $location_name = '';
        $table = '';
        if ($enabled_setting) {
          if (empty($this->reports['locations'])) {
            return FALSE;
          }
          $data['summary'] = $this->reports['locations']['total'];
          $data['summary']['name'] = t('ALL BRANCHES');
          $data['rows'] = $this->reports['locations'];
          // Sort by names.
          $names = [];
          foreach ($data['rows'] as &$name) {
            if (!isset($name["name"])) {
              continue;
            }
            $names[] = &$name["name"];
          }
          natcasesort($names);
          $variables = [
            '#theme' => 'yptf_kronos_report',
            '#data' => $data,
          ];
          // Drush can't render that 'render($variables);' cause it has miss
          // context instead use command below.
          $table = $this->renderer->renderRoot($variables);
        }
        break;
    }

    return ['report' => $table, 'name' => $location_name];
  }

  /**
   * Send error reports.
   */
  public function sendErrorReports() {
    if (isset($this->reports['messages']['error_reports'])) {
      $admin_emails = $config = $this->configFactory->get(
        'yptf_kronos.settings'
      )->get('admin_emails');
      if (!empty($admin_emails)) {
        $admin_emails = explode(',', $admin_emails);
        foreach ($admin_emails as $index => $email) {
          $email = trim($email);
          if (empty($email)) {
            continue;
          }
          try {
            // Send error notifications.
            $lang = $this->languageManager->getCurrentLanguage()->getId();
            $tokens['body'] = '';
            foreach ($this->reports['messages']['error_reports'] as $error_name => $error_values) {
              $tokens['body'] .= '<div><strong>' . (string) $error_name . '</strong></div>';
              foreach ($error_values as $error_description) {
                $tokens['body'] .= '<div>' . (string) $error_description . '</div>';
              }
              $tokens['body'] .= '<br>';
            }

            $tokens['subject'] = t('Kronos Error Reports');
            $this->mailManager->mail(
              'yptf_kronos',
              'yptf_kronos_error_reports',
              $email,
              $lang,
              $tokens
            );
          } catch (\Exception $e) {
            $msg = 'Failed to send Error email report. Error: %error';
            $this->logger->notice($msg, ['%error' => $e->getMessage()]);

          }
        }
      }
    }
  }

}
