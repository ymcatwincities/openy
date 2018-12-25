<?php

namespace Drupal\optimizely\Util;

/**
 * For handling the Optimizely account id number.
 */
class AccountId {

  private static $config = NULL;

  /**
   * Retrieve the config object for storing the settings.
   */
  private static function getConfig() {

    if (!self::$config) {
      self::$config = \Drupal::configFactory()->getEditable('optimizely.settings');
    }
    return self::$config;
  }

  /**
   * Retrieve the account id.
   */
  public static function getId() {

    $config = self::getConfig();
    $optimizely_id = $config->get('optimizely_id');
    return $optimizely_id;
  }

  /**
   * Store the account id.
   */
  public static function setId($id) {

    $config = self::getConfig();
    $config->set('optimizely_id', $id);
    $config->save();
    return TRUE;
  }

  /**
   * Delete the account id.
   *
   * Currently, only the account id is stored in the settings,
   * so we just delete everything stored in "optimizely.settings".
   */
  public static function deleteId() {
    $config = self::getConfig();
    $config->delete();
    return TRUE;
  }

}
