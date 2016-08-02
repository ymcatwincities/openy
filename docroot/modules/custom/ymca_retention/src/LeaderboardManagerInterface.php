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
   * Returns the Mappings of location bundle containing all the locations of
   * registered members.
   *
   * @return array
   *   An array of locations.
   */
  public function getMemberLocations();

  /**
   * Returns an array of locations branch ids and names for all the registered
   * members.
   *
   * @return array
   *   An array of locations.
   */
  public function getLocationsList();

}
