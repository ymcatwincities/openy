<?php

namespace Drupal\yptf_kronos;

/**
 * Class YptfKronosReportsBase
 *
 * @package Drupal\yptf_kronos
 */
class YptfKronosReportsBase implements YptfKronosReportsInterface {

  /**
   * The Reports data.
   *
   * @var array
   */
  protected $reports;

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
    $this->reports['messages']['error_reports'][$key][] = $item;
  }

}
