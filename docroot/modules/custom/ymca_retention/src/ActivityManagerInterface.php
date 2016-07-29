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
   * @param int $member_id
   *   Member ID to load activities for.
   *
   * @return array
   *   Tracked member activities.
   */
  public function getMemberActivities($member_id = NULL);

  /**
   * Returns an array of tracked member activities as Angular model.
   *
   * @param int $member_id
   *   Member ID to load activities for.
   *
   * @return array
   *   Tracked member activities.
   */
  public function getMemberActivitiesModel($member_id = NULL);

  /**
   * Returns URL to the member activities callback.
   *
   * @return string
   *   URL to the member activities callback.
   */
  public function getUrl();

}
