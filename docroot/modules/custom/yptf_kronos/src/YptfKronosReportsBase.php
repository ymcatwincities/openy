<?php

namespace Drupal\yptf_kronos;

/**
 * Class YptfKronosReportsBase
 *
 * @package Drupal\yptf_kronos
 */
class YptfKronosReportsBase implements YptfKronosReportsInterface {

  /**
   * MindBody CSV file format.
   */
  const MINDBODY_CSV_FILE_FORMAT = 'MB_%s--%s.csv';

  /**
   * MindBody CSV dir.
   */
  const MINDBODY_CSV_FILE_DIR = 'mb_kronos_reports';

  /**
   * Whether to send emails if there are errors.
   *
   * @var bool
   */
  protected $sendWithErrors = TRUE;

  /**
   * The Reports data.
   *
   * @var array
   */
  protected $reports;

  /**
   * MindBody data.
   *
   * @var array
   */
  protected $mindbodyData;

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
    $filesDir = \Drupal::service('file_system')->realpath(file_default_scheme() . "://") . '/' . self::MINDBODY_CSV_FILE_DIR;
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
      $empId = $this->getEmpIdByNameFromKronosData($rows[$i]['Staff'], $kronosData);
      if ($empId) {
        $rows[$i]['EmpID'] = $empId;
      }
      else {
        $notFound[] = $rows[$i]['Staff'];
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
      ['kronos' => 'Emma B Howe', 'mb' => 'Emma B. Howe - Coon Rapids YMCA', 'brcode' => 17],
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
   * @return array
   */
  protected function getSuppressedLocationIds() {
    return [5];
  }

}
