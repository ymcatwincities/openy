<?php

namespace Drupal\ymca_google;
use Drupal\ymca_groupex_google_cache\Entity\GroupexGoogleCache;
use Drupal\ymca_groupex_google_cache\GroupexGoogleCacheInterface;

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
   * Append item to proxy data.
   *
   * @param string $op
   *   Operation: insert, delete, update.
   * @param \Drupal\ymca_groupex_google_cache\GroupexGoogleCacheInterface $entity
   *   Entity.
   */
  public function appendProxyItem($op, GroupexGoogleCacheInterface $entity);

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
   * Set ICS data.
   *
   * @param array $data
   *   ICS Data.
   */
  public function setIcsData(array $data);

  /**
   * Get ICS data.
   */
  public function getIcsData();

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
