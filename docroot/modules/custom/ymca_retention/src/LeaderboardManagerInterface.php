<?php

namespace Drupal\ymca_retention;

/**
 * Defines a common interface for leaderboard managers.
 */
interface LeaderboardManagerInterface {

  /**
   * Returns an array of members for specified location branch_id.
   *
   * @param int $branch_id
   *   Branch id of the location.
   *
   * @return array
   *   An array of members for the leaderboard.
   */
  public function getLeaderboard($branch_id = 0);

  /**
   * Returns an array of locations with location branch id and name.
   *
   * @return array
   *   An array of locations.
   */
  public function getLocations();

}
