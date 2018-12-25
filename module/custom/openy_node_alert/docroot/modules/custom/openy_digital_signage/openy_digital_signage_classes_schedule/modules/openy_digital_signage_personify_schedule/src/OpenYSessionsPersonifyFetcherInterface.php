<?php

namespace Drupal\openy_digital_signage_personify_schedule;

/**
 * Provides an interface for Personify data fetcher service.
 *
 * @ingroup openy_digital_signage_personify_schedule
 */
interface OpenYSessionsPersonifyFetcherInterface {

  /**
   * Retrieves list of locations ids to be imported.
   *
   * @return array
   *   IDs of location to be imported.
   */
  public function getLocations();

  /**
   * Fetch from Personify.
   */
  public function fetchAll();

  /**
   * Check if there are entities to be deleted.
   *
   * @param array $feed
   *   Pulled Personify pro feed.
   *
   * @return array
   *   The array on entity ids to be deleted.
   */
  public function checkDeleted($feed);

  /**
   * Removes entities by chunks.
   *
   * @param array $ids
   *   Pulled Personify feed.
   */
  public function removeDeleted($ids);

  /**
   * Create or update sessions.
   *
   * @param array $data
   *   Data received from Personify.
   */
  public function processData(array $data);

}
