<?php

namespace Drupal\openy_digital_signage_groupex_schedule;

/**
 * Provides an interface for GroupEx Pro data fetcher service.
 *
 * @ingroup openy_digital_signage_groupex_schedule
 */
interface OpenYSessionsGroupExFetcherInterface {

  /**
   * Get default options for a request to GroupEx Pro.
   *
   * @return array
   *   Default options for a request.
   */
  public function defaultOptions();

  /**
   * Retrieves list of locations ids to be imported.
   *
   * @return array
   */
  public function getLocations();

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

  /**
   * Check if there are entities to be deleted.
   *
   * @param array $feed
   *   Pulled GroupEx pro feed.
   * @param int $location_id
   *   The location id.
   *
   * @return array
   *   The array on entity ids to be deleted.
   */
  public function checkDeleted($feed, $location_id);

  /**
   * Removes entities by chunks.
   *
   * @param array $feed
   *   Pulled GroupEx pro feed.
   * @param int $location_id
   *   The location id.
   *
   * @return array
   *   The array on entity ids to be deleted.
   */
  public function removeDeleted($ids);

  /**
   * Create or update sessions.
   *
   * @param array $data
   *   Data received from GroupEx Pro.
   * @param int $location_id
   *   Location node id.
   */
  public function processData(array $data, $location_id);

}
