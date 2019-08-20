<?php

namespace Drupal\openy_myy\PluginManager;

/**
 * Interface MyYDataProfileInterface
 *
 * @package Drupal\openy_myy\PluginManager
 */
interface MyYDataProfileInterface {

  /**
   * Get user object fields
   *
   * @return mixed
   */
  public function getProfileData();

  /**
   * Render link to user avatar.
   *
   * @return mixed
   */
  public function getProfileAvatar();

  /**
   * Get family members info.
   *
   * @return mixed
   */
  public function getFamilyInfo();

  /**
   * Get Health information.
   *
   * @return mixed
   */
  public function getProfileHealthInformation();



}
