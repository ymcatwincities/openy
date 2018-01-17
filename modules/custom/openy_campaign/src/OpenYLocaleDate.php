<?php

/**
 * @file
 * This file is to be used for Locale date conversions and dates that need
 * to be changed in and out via timezones.
 */

namespace Drupal\openy_campaign;

use \DateTime;
use \DateTimeZone;
use Drupal\Core\Render\Element\Date;

class OpenYLocaleDate {

  protected $date;
  protected $siteTimezone;
  protected $convertedTimezone;

  function __construct(DateTime $date = NULL, DateTimeZone $timezone = NULL) {
    $this->setDateFromDateTime($date, $timezone);
    $this->siteTimezone = date_default_timezone_get();
  }


  /**
   * Returns the datetime object.
   * @return \DateTime
   */
  public function getDate(): DateTime {
    return $this->date;
  }

  /**
   * Returns the datetime timestamp.
   * @return int
   */
  public function getTimestamp(): int {
    return $this->date->getTimestamp();
  }

  /**
   * Returns whether or not the date has passed from the current time.
   *
   * We need to go through an awkward process of converting to and from
   * the timezone due to issues with UTC and locales. This puts all dates
   * essentially as UTC.
   *
   * @return bool
   */
  public function dateHasPassed(): bool {
    $fromTimeZone = !empty($this->convertedTimezone) ? $this->convertedTimezone : new DateTimeZone($this->siteTimezone);
    $localeCurrent = new OpenYLocaleDate();
    $localeCurrent->setDateByTimezone(new DateTimeZone($this->siteTimezone));
    $localeCurrent->convertTimezone($fromTimeZone);
    return $localeCurrent->getDate() >= $this->getDate();
  }


  /**
   * Sets the utc date from one timezone to another, defaulting to site default.
   *
   * This function is useful to convert a date converted to another timezone
   * back into the site default, but can be used to convert a timezone to
   * any timezone.
   *
   * @param \DateTimeZone $fromTimezone
   *  The timezone to convert the UTC date from.
   *
   * @param \DateTimeZone|null $toTimezone
   *  The timezone to convert the UTC date to.
   *
   * @return \DateTime
   */
  public function convertTimezone(DateTimeZone $fromTimezone, DateTimeZone $toTimezone = NULL): DateTime {
    $toTimezone = !empty($toTimezone) ? $toTimezone : new DateTimeZone($this->siteTimezone);
    $this->convertedTimezone = $fromTimezone;
    $new_date = new DateTime();
    $timestamp = $this->getDate()->getTimestamp() - $toTimezone->getOffset($this->getDate()) + $fromTimezone->getOffset($this->getDate());
    $new_date->setTimestamp($timestamp);
    $new_date->setTimezone($toTimezone);
    $this->date = $new_date;
    return $new_date;
  }

  /**
   * Update the date with the offset from a given timezone.
   *
   * @param $timezone
   *  Can be either a string or a DateTimeZone object.
   */
  public function setDateByTimezone($timezone) {
    if (is_string($timezone)) {
      $timezone = new DateTimeZone($timezone);
    }
    $this->convertedTimezone = $timezone;
    $new_date_utc = $this->date->getTimeStamp() - $timezone->getOffset($this->date);
    $new_date = new DateTime();
    $new_date->setTimestamp($new_date_utc);
    $this->date = $new_date;
  }

  /**
   * Sets the date from a datetime object.
   *
   * @param \DateTime|NULL $date
   * @param \DateTimeZone|null $dateTimeZone
   */
  public function setDateFromDateTime(DateTime $date = NULL, DateTimeZone $dateTimeZone = NULL) {
    $dateTimeZone = !empty($dateTimeZone) ? $dateTimeZone : new DateTimeZone('UTC');
    $this->date = !empty($date) ? $date : new DateTime($date);
    $this->date->setTimezone($dateTimeZone);
  }

  /**
   * Sets the date from a datetime accepted formatted string.
   *
   * @param string $date
   */
  public function setDateFromFormat(string $date = 'now', DateTimeZone $dateTimeZone = NULL) {
    $dateTimeZone = !empty($dateTimeZone) ? $dateTimeZone : new DateTimeZone('UTC');
    $this->date = new DateTime($date);
    $this->date->setTimezone($dateTimeZone);
  }

  /**
   * Sets the date from a unix timestamp.
   *
   * The date should always be UTC.
   *
   * @param string $timestamp
   * @param \DateTimeZone $dateTimeZone
   */
  public function setDateFromTimeStamp(string $timestamp, DateTimeZone $dateTimeZone) {
    $dateTimeZone = !empty($dateTimeZone) ? $dateTimeZone : new DateTimeZone('UTC');
    $this->date = new DateTime('now');
    $this->date->setTimestamp($timestamp);
    $this->date->setTimezone($dateTimeZone);
  }

  /**
   * Creates a new OpenYLocaleDate from a formatted date string.
   *
   * @param string $date
   * @param \DateTimeZone|null $dateTimeZone
   *
   * @return \Drupal\openy_campaign\OpenYLocaleDate
   */
  public static function createDateFromFormat(string $date = 'now', DateTimeZone $dateTimeZone = NULL): OpenYLocaleDate {
    $openYLocaleDate = new OpenYLocaleDate();
    $openYLocaleDate->setDateFromFormat($date, $dateTimeZone);
    return $openYLocaleDate;
  }

  /**
   * Creates a new OpenYLocaleDate from a timestamp.
   *
   * @param string $timestamp
   * @param \DateTimeZone|null $dateTimeZone
   *
   * @return \Drupal\openy_campaign\OpenYLocaleDate
   */
  public static function createDateFromTimestamp(string $timestamp, DateTimeZone $dateTimeZone = NULL): OpenYLocaleDate {
    $openYLocaleDate = new OpenYLocaleDate();
    $openYLocaleDate->setDateFromTimeStamp($timestamp, $dateTimeZone);
    return $openYLocaleDate;
  }

  /**
   * Creates a new OpenYLocaleDate from a DateTime object.
   *
   * @param \DateTime $dateTime
   * @param \DateTimeZone|null $dateTimeZone
   *
   * @return \Drupal\openy_campaign\OpenYLocaleDate
   */
  public static function createDateFromDateTime(DateTime $dateTime, DateTimeZone $dateTimeZone = NULL): OpenYLocaleDate {
    $openYLocaleDate = new OpenYLocaleDate();
    $openYLocaleDate->setDateFromDateTime($dateTime, $dateTimeZone);
    return $openYLocaleDate;
  }

}