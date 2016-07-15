<?php

namespace Drupal\ymca_retention;

/**
 * Defines a common interface for activity managers.
 */
interface ActivityManagerInterface {

  /**
   * Returns an array of dates for the campaign activities.
   *
   * @return array
   *   An array of dates for the activities tracking.
   */
  public function getDates();

  /**
   * Returns an array of activity groups.
   *
   * @return array
   *   An array of activity groups.
   */
  public function getActivityGroups();

  /**
   * Returns an array of tracked member activities.
   *
   * @return array
   *   Tracked member activities.
   */
  public function getMemberActivities();

}
