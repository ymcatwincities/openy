<?php

namespace Drupal\ymca_google;

/**
 * Interface GcalGroupexWrapperInterface.
 *
 * @package Drupal\ymca_google
 */
interface GcalGroupexWrapperInterface {

  /**
   * Array of entities to be cached and enriched on host system.
   *
   * @return array
   *   Proxy data.
   */
  public function getProxyData();

  /**
   * Set proxy data.
   *
   * @param array $data
   *   Proxy data.
   */
  public function setProxyData(array $data);

  /**
   * Get array of source data.
   *
   * @return array
   *   Array of source data.
   */
  public function getSourceData();

  /**
   * Set source data.
   *
   * @param array $data
   *   Proxy data.
   */
  public function setSourceData(array $data);

  /**
   * Set time frame.
   *
   * @param array $frame
   *   Array with start and stop.
   */
  public function setTimeFrame(array $frame);

  /**
   * Get time frame.
   *
   * @return mixed
   *   Array with time frame.
   */
  public function getTimeFrame();

  /**
   * Get schedule.
   *
   * @return mixed
   *   Schedule.
   */
  public function getSchedule();

  /**
   * Update schedule and move pointer.
   */
  public function next();

}
