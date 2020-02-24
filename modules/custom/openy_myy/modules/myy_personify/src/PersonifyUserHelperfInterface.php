<?php

namespace Drupal\myy_personify;

/**
 * Interface PersonifyUserHelperfInterface
 *
 * @package Drupal\myy_personify
 */
interface PersonifyUserHelperfInterface {

  /**
   * Helper method that get's user id based on personify session.
   *
   * @return mixed
   */
  public function personifyGetId();

  /**
   * Helper method that map's branch id to title.
   *
   * @param $branch_id
   *
   * @return mixed
   */
  public function locationMapping($branch_id);

}
