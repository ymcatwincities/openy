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

  /**
   * @param array $profile
   *
   * @return mixed
   */
  public function updateProfileFields(array $profile);

  /**
   * @param $old_pwd
   * @param $new_pwd
   *
   * @return mixed
   */
  public function updateProfilePassword($old_pwd, $new_pwd);

  /**
   * @param array $phone
   *
   * @return mixed
   */
  public function updateProfilePhoneNumber(array $phone);

  /**
   * @param array $contact_data
   *
   * @return mixed
   */
  public function updateEmergencyContact(array $contact_data);

  /**
   * @param array $contact_data
   *
   * @return mixed
   */
  public function addEmergencyContact(array $contact_data);

  /**
   * @param array $health_info
   *
   * @return mixed
   */
  public function updateHealthInformation(array $health_info);

  /**
   * @return mixed
   */
  public function getMembershipInfo();

  /**
   * @return mixed
   */
  public function getGuestPasses();

}
