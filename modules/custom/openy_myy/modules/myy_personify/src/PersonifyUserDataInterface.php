<?php

namespace Drupal\myy_personify;

/**
 * Interface PersonifyUserDataInterface
 *
 * @package Drupal\myy_personify
 */
interface PersonifyUserDataInterface {

  /**
   * Helper method that constructs menu links for family member.
   *
   * @return mixed
   */
  public function getHouseholdProfileLinks();

  /**
   * Helper method that constructs family members data.
   *
   * @return mixed
   */
  public function getFamilyData();

}
