<?php

namespace Drupal\yptf_kronos;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Component\Utility\Unicode;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Core\Render\Renderer;
use Drupal\mindbody_cache_proxy\MindbodyCacheProxy;

/**
 * Class YptfKronosReports.
 *
 * @package Drupal\yptf_kronos
 */
class YptfKronosReports {

  /**
   * MindBody CSV file format.
   */
  const MINDBODY_CSV_FILE_FORMAT = 'MB_%s--%s.csv';

  /**
   * Whether to send emails if there are errors.
   *
   * @var bool
   */
  protected $sendWithErrors = TRUE;

  /**
   * Day of report to generate.
   *
   * @var string
   */
  protected $reportDay;

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
   * Indicates that we should search Kronos file by fixed dates.
   *
   * @var bool
   */
  protected $fixedKronosDates;

  /**
   * Mindbody dir with CSV files.
   *
   * @var string
   */
  protected $mindBodyCSVFileDir = 'mb_kronos_reports';

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
   * Generate Reports.
   *
   * @param string $day
   *   Day. 'tuesday' or 'monday'.
   *
   * @return bool
   *   FALSE in case .
   */
  public function generateReports($day) {
    if (!$this->dayIsSupported($day)) {
      return FALSE;
    }

    $this->reportDay = $day;

    try {
      $this->logger->info(sprintf('Kronos %s email reports generator started.', Unicode::ucfirst($this->reportDay)));
      $this->getInitialDates();
      $this->sendReports();
      $this->sendErrorReports();
      $this->logger->info(sprintf('Kronos %s email reports generator finished.', Unicode::ucfirst($this->reportDay)));
    }
    catch (\Exception $e) {
      $this->logger->error(
        'Failed to run reports for %day. Error: %error',
        [
          '%day' => Unicode::ucfirst($this->reportDay),
          '%error' => $e->getMessage()
        ]
      );
      return FALSE;
    }

    return TRUE;
  }

  /**
   * Checks if day is supported.
   *
   * @param string $day
   *   Day name.
   *
   * @return bool
   *   TRUE if day is supported.
   */
  private function dayIsSupported($day) {
    if (!in_array($day, ['tuesday', 'monday'])) {
      $this->logger->error('Report type "%type" is not supported.', ['%type' => $day]);
      return FALSE;
    }

    return TRUE;
  }

  /**
   * Generates HTML reports.
   *
   * @param string $day
   *   Day of the report.
   * @param array $dates
   *   Keyed array with "start" and "end" timestamps.
   *
   * @return string
   *   HTML for report.
   *
   */
  public function generateHtmlReports($day, $dates = []) {
    if (!$this->dayIsSupported($day)) {
      drupal_set_message(t('Day is not supported'), 'error');
      return FALSE;
    }

    if (empty($dates)) {
      drupal_set_message(t('Timestamps for "start" and "end" dates are required.'), 'error');
      return FALSE;
    }

    $this->mindBodyCSVFileDir = 'mb_kronos_history';
    $this->reportDay = $day;
    $this->fixedKronosDates = TRUE;
    $this->dates['StartDate'] = date('Y-m-d', $dates[0]);
    $this->dates['EndDate'] = date('Y-m-d', $dates[1]);

    try {
      $this->calculateReports();
    }
    catch (\Exception $e) {
      drupal_set_message($e->getMessage(), 'error');
      return FALSE;
    }

    $output = '';
    $reportLeadership = null;
    $reports = [];

    /** @var \Drupal\ymca_mappings\LocationMappingRepository $locationRepository */
    $locationRepository = \Drupal::service('ymca_mappings.location_repository');
    $locations = $locationRepository->loadAllLocationsWithMindBodyId();
    foreach ($locations as $mapping) {
      $location = $mapping->get('field_location_ref')->first()->get('entity')->getTarget()->getValue();
      if (!$location) {
        $this->logger->error('Failed to load location from mapping %id', ['%id' => $mapping->id()]);
        continue;
      }

      if (is_null($reportLeadership)) {
        $reportLeadership = $this->createReportTable($location->id())['report'];
      }

      $table = $this->createReportTable($location->id(), 'pt_managers');
      $reports[$location->id()] = [
        'id' => $location->id(),
        'name' => $location->label(),
        'table' => $table['report'],
      ];
    }

    $output .= '<h4>Leadership Report</h4>';
    $output .= $reportLeadership;

    foreach ($reports as $reportItem) {
      $output .= "<h4>$reportItem[name]</h4>";
      $output .= $reportItem['table'];
    }

    return $output;
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

          // Skip this location if it's suppressed.
          if (in_array($location_id, $this->getSuppressedLocationIds())) {
            continue;
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
        // Get MB location ID.
        $brCode = $this->getBranchCodeIdByMBLocationName($item['Location']);
        if (!$brCode) {
          throw new \Exception(sprintf('Failed to get Personify Location ID by MB Location Name: %s', $item['Location']));
        }
        $location_id = $mapping_repository_location->findMindBodyIdByPersonifyId($brCode);
        if (!$location_id) {
          throw new \Exception(sprintf('Failed to get MindBody Location ID by Personify ID: %d', $item->locNo));
        }

        // Skip this location if it's suppressed.
        if (in_array($location_id, $this->getSuppressedLocationIds())) {
          continue;
        }

        $diff = (float) $item['HoursBooked'];

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

        !isset($trainer_reports[$location_id][$staff_id]['mb_hours']) ? $trainer_reports[$location_id][$staff_id]['mb_hours'] = 0 : '';
        $trainer_reports[$location_id][$staff_id]['mb_hours'] += $diff;
        !empty($trainer_name) ? $trainer_reports[$location_id][$staff_id]['name'] = $trainer_name : '';

        !isset($location_reports[$location_id]['mb_hours']) ? $location_reports[$location_id]['mb_hours'] = 0 : '';
        $location_reports[$location_id]['mb_hours'] += $diff;
        !empty($item['Location']) ? $location_reports[$location_id]['name'] = $item['Location'] : '';
      }
    }

    // Calculate variance for trainers.
    foreach ($trainer_reports as $location_id => &$trainers) {
      foreach ($trainers as &$trainer) {
        $mb_flag = $wf_flag = TRUE;
        if (!isset($trainer['mb_hours'])) {
          $mb_flag = FALSE;
          $trainer['variance'] = '-';
          $trainer['mb_hours'] = '-';
        }
        if (!isset($trainer['wf_hours'])) {
          $wf_flag = FALSE;
          $trainer['variance'] = '-';
          $trainer['wf_hours'] = '-';
        }
        if ($mb_flag && $wf_flag) {
          // We can't divide by zero, setting variance to be "-" in that case.
          if ($trainer['wf_hours'] != 0)  {
            $trainer['variance'] = round((1 - $trainer['mb_hours'] / $trainer['wf_hours']) * 100);
            $trainer['variance'] .= '%';
          }
          else {
            $trainer['variance'] = '-';
          }
        }

        if (!isset($trainer['historical_hours'])) {
          $trainer['historical_hours'] = '-';
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
        $row['variance'] = round((1 - $row['mb_hours'] / $row['wf_hours']) * 100);
        $row['variance'] .= '%';
      }
      isset($row['wf_hours']) ? $loc_total['wf_hours'] += $row['wf_hours'] : '';
      isset($row['mb_hours']) ? $loc_total['mb_hours'] += $row['mb_hours'] : '';
      isset($row['historical_hours']) ? $loc_total['historical_hours'] += $row['historical_hours'] : '';
    }
    $location_reports['total']['wf_hours'] = $loc_total['wf_hours'];
    $location_reports['total']['mb_hours'] = $loc_total['mb_hours'];
    if (isset($loc_total['total']['historical_hours'])) {
      $location_reports['total']['historical_hours'] = $loc_total['total']['historical_hours'];
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
   *
   * @throws \Exception
   */
  public function getKronosData() {
    $fileMapping = [
      'monday' => 'WFCMon',
      'tuesday' => 'WFC'
    ];

    // Wrap here old code.
    if (!$this->fixedKronosDates) {
      $this->kronosData = FALSE;
      $kronos_report_day = $this->kronosReportDay;
      $kronos_shift_days = $this->kronosReportShiftDays;
      $kronos_file_name_date = date('Y-m-d', strtotime($kronos_report_day));
      $kronos_data_raw = $kronos_file = '';
      foreach ($kronos_shift_days as $shift) {
        $kronos_file_name_date = date('Y-m-d', strtotime($kronos_report_day . $shift . 'days'));
        $kronos_path_to_file = \Drupal::service('file_system')->realpath(file_default_scheme() . "://");
        $file_prefix = $fileMapping[$this->reportDay];
        $kronos_file = sprintf('%s/wf_reports/%s_%s.json', $kronos_path_to_file, $file_prefix, $kronos_file_name_date);

        file_exists($kronos_file) ? $kronos_data_raw = file_get_contents($kronos_file) : '';
        if (!empty($kronos_data_raw)) {
          break;
        }
      }

      if (!$kronos_data_raw) {
        throw new \Exception(sprintf('Failed to load Kronos file: %s', $kronos_file));
      }

      $this->dates['EndDate'] = date('Y-m-d', strtotime($kronos_file_name_date));
      $this->dates['StartDate'] = date('Y-m-d', strtotime($kronos_file_name_date . ' -13 days'));
    }

    $kronos_file_name_date = date('Y-m-d', strtotime($this->dates['EndDate']));
    $kronos_path_to_file = \Drupal::service('file_system')->realpath(file_default_scheme() . "://");
    $file_prefix = $fileMapping[$this->reportDay];
    $kronos_file = sprintf('%s/wf_reports/%s_%s.json', $kronos_path_to_file, $file_prefix, $kronos_file_name_date);

    if (!file_exists($kronos_file)) {
      throw new \Exception(sprintf('Failed to load Kronos file: %s', $kronos_file));
    }

    if (!$kronos_data_raw = file_get_contents($kronos_file)) {
      throw new \Exception(sprintf('Failed to load data from Kronos file: %s', $kronos_file));
    }

    return $this->kronosData = json_decode($kronos_data_raw);
  }

  /**
   * Send reports.
   */
  public function sendReports() {
    $config = $this->configFactory->get($this->getCurrentConfigName());

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
          str_replace('&nbsp;', '', $config->get($report_type)['disabled_message']['value'])
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
          $tokens['subject'] = str_replace('[report-start-date]', date("m/d/Y", strtotime($this->dates['StartDate'])), $tokens['subject']);
          $tokens['subject'] = str_replace('[report-end-date]', date("m/d/Y", strtotime($this->dates['EndDate'])), $tokens['subject']);

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
            throw new \Exception(sprintf('Failed to send email with error: %s', $e->getMessage()));
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
   *
   * @throws \Exception
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
          throw new \Exception(sprintf('Failed to find Location MindBody ID, by location ID: %d', $location_id));
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
            $admin_emails = $config = $this->configFactory->get($this->getCurrentConfigName())->get('admin_emails');
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
      $admin_emails = $config = $this->configFactory->get($this->getCurrentConfigName())->get('admin_emails');
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

  /**
   * Get Personify ID (branch code in Kronos) by MB location name.
   *
   * @param string $name
   *   MindBody location name.
   *
   * @return null
   */
  protected function getBranchCodeIdByMBLocationName($name) {
    $mapping = $this->getLocationNamesMapping();
    foreach ($mapping as $item) {
      if (trim($name) === trim($item['mb'])) {
        return $item['brcode'];
      }
    }

    return NULL;
  }

  /**
   * Return suppressed location IDs (MindBody IDs)
   *
   * @return array
   *   List of MindBody location IDs to suppress.
   */
  protected function getSuppressedLocationIds() {
    return [5];
  }

  /**
   * Check whether reports has errors.
   *
   * @return bool
   *   TRUE if there are errors.
   */
  protected function hasErrors() {
    if (isset($this->reports['messages']['error_reports']) && !empty($this->reports['messages']['error_reports'])) {
      return TRUE;
    }

    return FALSE;
  }

  /**
   * Add error to the array of errors.
   *
   * @param $key
   *   Error key. Will be marked with bold in the email.
   * @param $item
   *   Error description.
   */
  protected function addError($key, $item) {
    // Prevent duplicates.
    if (
      isset($this->reports['messages']) &&
      isset($this->reports['messages']['error_reports']) &&
      !in_array($item, $this->reports['messages']['error_reports'][$key])
    ) {
      $this->reports['messages']['error_reports'][$key][] = $item;
    }
  }

  /**
   * Get empNo from raw Kronos data.
   *
   * @param string $name
   *   First + Last names.
   * @param array $kronosData
   *   Raw kronos data.
   *
   * @return bool
   *   String if something found. FALSE if there is no match.
   */
  protected function getEmpIdByNameFromKronosData($name, array $kronosData) {
    foreach ($kronosData as $item) {
      $computedName = sprintf('%s %s', $item->firstName, $item->lastName);
      if (trim($computedName) == trim($name)) {
        return $item->empNo;
      }
    }

    return FALSE;
  }

  /**
   * Returns report dates.
   *
   * @return mixed
   *   Array with "StartDate" & "EndDate".
   *
   * @throws \Exception
   */
  protected function getReportDates() {
    if (!isset($this->dates)) {
      throw new \Exception('Failed to get dates for report generating.');
    }

    $dates = $this->dates;

    if (!isset($dates["StartDate"]) || empty($dates["StartDate"])) {
      throw new \Exception('Failed to get start date for the report.');
    }

    if (!isset($dates["EndDate"]) || empty($dates["EndDate"])) {
      throw new \Exception('Failed to get end date for the report.');
    }

    return $dates;
  }

  /**
   * Get data from exported MindBody CSV file.
   *
   * @param array $kronosData
   *   Raw Kronos data.
   *
   * @return array
   *   List of rows with MindBody data.
   *
   * @throws \Exception
   */
  protected function getMindBodyCSVData(array $kronosData) {
    $reportDates = $this->getReportDates();
    $fileName = sprintf('MB_%s--%s.csv', $reportDates['StartDate'], $reportDates['EndDate']);
    $filesDir = \Drupal::service('file_system')->realpath(file_default_scheme() . "://") . '/' . $this->mindBodyCSVFileDir;
    $filePath = $filesDir . '/' . $fileName;

    if (!file_exists($filePath)) {
      $message = sprintf('The file with MindBody data was not found: %s. Please, upload the file', $filePath);
      throw new \Exception($message);
    }

    $csvData = file_get_contents($filePath);
    $lines = explode(PHP_EOL, $csvData);
    $titles = str_getcsv($lines[1]);

    // Remove headers.
    array_splice ($lines, 0,2);

    $rows = [];
    $notFound = [];
    foreach ($lines as $i => $line) {
      // Skip the last empty line.
      if (empty($line)) {
        continue;
      }

      $row = str_getcsv($line);
      $rows[$i] = array_combine($titles, $row);

      if (!isset($rows[$i]['EmpID']) || empty($rows[$i]['EmpID'])) {
        $empId = $this->getEmpIdByNameFromKronosData($rows[$i]['Staff'], $kronosData);
        if ($empId) {
          $rows[$i]['EmpID'] = $empId;
        }
        else {
          $notFound[] = $rows[$i]['Staff'];
        }
      }

    }

    $this->mindbodyData = $rows;
    return $this->mindbodyData;
  }

  /**
   * Returns location mapping.
   *
   * @return array
   *   Mapping array.
   */
  protected function getLocationNamesMapping() {
    return [
      ['kronos' => 'Andover', 'mb' => 'Andover YMCA', 'brcode' => 32],
      ['kronos' => 'Blaisdell', 'mb' => 'Blaisdell YMCA', 'brcode' => 14],
      ['kronos' => 'Burnsville', 'mb' => 'Burnsville YMCA', 'brcode' => 30],
      ['kronos' => 'Eagan', 'mb' => 'Eagan YMCA', 'brcode' => 82],
      ['kronos' => 'Elk River', 'mb' => 'Elk River YMCA', 'brcode' => 34],
      ['kronos' => 'Emma B Howe', 'mb' => 'Emma B. Howe - Coon Rapids YMCA', 'brcode' => 27],
      ['kronos' => 'Forest Lake', 'mb' => 'Forest Lake YMCA', 'brcode' => 38],
      ['kronos' => 'Hastings', 'mb' => 'Hastings YMCA', 'brcode' => 85],
      ['kronos' => 'Hudson', 'mb' => 'Hudson YMCA', 'brcode' => 84],
      ['kronos' => 'Lino Lakes', 'mb' => 'Lino Lakes YMCA', 'brcode' => 81],
      ['kronos' => 'Maplewood Comm Ctr', 'mb' => 'Maplewood YMCA', 'brcode' => 87],
      ['kronos' => 'New Hope', 'mb' => 'New Hope YMCA', 'brcode' => 24],
      ['kronos' => 'Ridgedale', 'mb' => 'Ridgedale YMCA', 'brcode' => 22],
      ['kronos' => 'River Valley', 'mb' => 'River Valley YMCA', 'brcode' => 36],
      ['kronos' => 'Rochester', 'mb' => 'Rochester YMCA', 'brcode' => 50],
      ['kronos' => 'Shoreview', 'mb' => 'Shoreview YMCA', 'brcode' => 89],
      ['kronos' => 'Southdale', 'mb' => 'Southdale YMCA', 'brcode' => 20],
      ['kronos' => 'St Paul Downtown', 'mb' => 'St. Paul Downtown YMCA', 'brcode' => 75],
      ['kronos' => 'St Paul Eastside', 'mb' => 'St. Paul Eastside YMCA', 'brcode' => 76],
      ['kronos' => 'West St Paul', 'mb' => 'West St. Paul YMCA', 'brcode' => 70],
      ['kronos' => 'White Bear Lake', 'mb' => 'White Bear Lake YMCA', 'brcode' => 88],
      ['kronos' => 'Woodbury', 'mb' => 'Woodbury YMCA', 'brcode' => 83],
      ['kronos' => 'Mpls Downtown', 'mb' => 'Dayton YMCA', 'brcode' => 17],
      ['kronos' => 'Heritage Park', 'mb' => 'Cora McCorvey YMCA', 'brcode' => 18],
      ['kronos' => 'St Paul Midway', 'mb' => 'Midway YMCA', 'brcode' => 77],
    ];
  }

  /**
   * Get config name depending on day of report.
   *
   * @return string
   *   Config name.
   *
   * @throws \Exception
   */
  protected function getCurrentConfigName() {
    $mapping = [
      'monday' => 'yptf_kronos_monday.settings',
      'tuesday' => 'yptf_kronos.settings',
    ];

    if (!array_key_exists($this->reportDay, $mapping)) {
      throw new \Exception(sprintf('Failed to load config name for day %s', $this->reportDay));
    }

    return $mapping[$this->reportDay];
  }

}
