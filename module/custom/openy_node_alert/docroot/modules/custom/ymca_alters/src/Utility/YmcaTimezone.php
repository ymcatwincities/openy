<?php

namespace Drupal\ymca_alters\Utility;

/**
 * Global trait to work with YMCA timezone.
 */
trait YmcaTimezone {

  /**
   * Returns YMCA timezone.
   */
  public function getYmcaTimezone() {
    return 'America/Chicago';
  }

  /**
   * Create date object from string and returns formatted string in appropriate format.
   *
   * @param string $time
   *   Datetime string.
   * @param string $from_format
   *   Format of incoming datetime.
   * @param string $to_format
   *   Format for outcoming string.
   *
   * @return string
   *   Formatted datetime.
   */
  public function formatDateTimeString($time, $from_format, $to_format) {
    $timezone = new \DateTimeZone($this->getYmcaTimezone());
    $date = \DateTime::createFromFormat($from_format, $time, $timezone);

    return $date->format($to_format);
  }

}
