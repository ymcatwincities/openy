<?php

namespace Drupal\myy_personify\Plugin\MyYDataProfile;

use Drupal\openy_myy\PluginManager\MyYDataProfileInterface;

/**
 * Personify Profile data plugin.
 *
 * @MyYDataProfile(
 *   id = "myy_personify_data_profile",
 *   label = "MyY Data Profile: Personify",
 *   description = "Profile data communication using Personify"
 * )
 */
class PersonifyDataProfile implements MyYDataProfileInterface {

  public function getProfileData() {
    // TODO: Implement getProfileData() method.
  }

  public function getProfileAvatar() {
    // TODO: Implement getProfileAvatar() method.
  }

  public function getProfileHealthInformation() {
    // TODO: Implement getProfileHealthInformation() method.
  }

  public function updateHealthInformation(array $health_info) {
    // TODO: Implement updateHealthInformation() method.
  }

  public function getFamilyInfo() {
    // TODO: Implement getFamilyInfo() method.
  }

  public function addEmergencyContact(array $contact_data) {
    // TODO: Implement addEmergencyContact() method.
  }

  public function updateEmergencyContact(array $contact_data) {
    // TODO: Implement updateEmergencyContact() method.
  }
  public function updateProfileFields(array $profile) {
    // TODO: Implement updateProfileFields() method.
  }
  public function updateProfilePassword($old_pwd, $new_pwd) {
    // TODO: Implement updateProfilePassword() method.
  }
  public function updateProfilePhoneNumber(array $phone) {
    // TODO: Implement updateProfilePhoneNumber() method.
  }

}