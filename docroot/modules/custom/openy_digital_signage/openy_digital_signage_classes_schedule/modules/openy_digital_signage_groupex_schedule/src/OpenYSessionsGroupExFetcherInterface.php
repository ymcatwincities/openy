<?php

namespace Drupal\openy_digital_signage_groupex_schedule;

/**
 * Provides an interface for GroupEx Pro data fetcher service.
 *
 * @ingroup openy_digital_signage_groupex_schedule
 */
interface OpenYSessionsGroupExFetcherInterface {

  /**
   * Import sessions for specific location.
   *
   * @param int $location_id
   *   Mapping location id.
   */
  public function fetchLocation($location_id);

  /**
   * Fetch from GroupEx Pro.
   */
  public function fetchAll();

}
