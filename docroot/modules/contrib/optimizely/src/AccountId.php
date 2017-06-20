<?php

namespace Drupal\optimizely;

class AccountId {

  private static $config = NULL;

  private static function getConfig() {

    if (! self::$config) {
      self::$config = \Drupal::configFactory()->getEditable('optimizely.settings');
    }
    return self::$config;
  }

  public static function getId() {

    $config = self::getConfig();
    $optimizely_id = $config->get('optimizely_id');
    return $optimizely_id;
  }

  public static function setId($id) {

    $config = self::getConfig();
    $config->set('optimizely_id', $id);
    $config->save();
    return TRUE;
  }

  public static function deleteId() {
    // N.B. This deletes any and all settings
    // stored in "optimizely.settings". 

    $config = self::getConfig();
    $config->delete();
    return TRUE;
  }
}
