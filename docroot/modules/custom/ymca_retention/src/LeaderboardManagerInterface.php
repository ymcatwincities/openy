<?php

namespace Drupal\ymca_retention;

/**
 * Defines a common interface for leaderboard managers.
 */
interface LeaderboardManagerInterface {
  /**
   * Returns an array of members for specified location branch_id.
   * 
   * @param $branch_id
   *   Branch id of the location.
   *
   * @return array
   *   An array of members for the leaderboard.
   */
  public function getLeaderboard($branch_id = 0);
}
