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
   * Returns all branch IDs of registered members.
   *
   * @return array
   *   An array of members branch IDs.
   */
  public function getMemberBranches();

  /**
   * Returns an array of the Mappings of location bundle.
   *
   * The returned array contains all the locations of registered members.
   *
   * @return array
   *   An array of locations.
   */
  public function getMemberLocations();

  /**
   * Returns an array of locations for all the registered members.
   *
   * The returned array contains locations branch id and name.
   *
   * @param bool $none
   *   Include "Select location..." option or not.
   *
   * @return array
   *   An array of locations.
   */
  public function getLocationsList($none = TRUE);

}
