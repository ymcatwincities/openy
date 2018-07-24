<?php

namespace Drupal\yptf_kronos;

/**
 * Class YptfKronosReportsBase
 *
 * @package Drupal\yptf_kronos
 */
class YptfKronosReportsBase implements YptfKronosReportsInterface {

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
    if (!in_array($item, $this->reports['messages']['error_reports'][$key])) {
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
   * Get data from exported MindBody CSV file.
   *
   * @param array $kronosData
   *   Raw Kronos data.
   *
   * @return array
   *   List of rows with MindBody data.
   */
  protected function getMindBodyCSVData(array $kronosData) {
    $filePath = drupal_get_path('module', 'yptf_kronos') . '/misc/mb_data_example.csv';
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

}
