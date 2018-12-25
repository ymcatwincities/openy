<?php

namespace Drupal\acquia_connector\Helper;

/**
 * Class Storage.
 *
 * Single centralized place for accessing and updating Acquia Connector
 * settings. All currently existing configs should be moved here and use Drupal
 * State API instead of Drupal Config. For more info visit
 * https://www.drupal.org/node/2635138.
 */
class Storage {

  /**
   * Returns Acquia Subscription identifier.
   *
   * @return mixed
   *   Acquia Subscription identifier.
   */
  public function getIdentifier() {
    return \Drupal::state()->get('acquia_connector.identifier');
  }

  /**
   * Returns Acquia Subscription key.
   *
   * @return mixed
   *    Acquia Subscription key.
   */
  public function getKey() {
    return \Drupal::state()->get('acquia_connector.key');
  }

  /**
   * Updates Acquia Subscription identifier.
   *
   * @param string $value
   *    Acquia Subscription identifier.
   */
  public function setIdentifier($value) {
    \Drupal::state()->set('acquia_connector.identifier', $value);
  }

  /**
   * Updates Acquia Subscription key.
   *
   * @param string $value
   *    Acquia Subscription key.
   */
  public function setKey($value) {
    \Drupal::state()->set('acquia_connector.key', $value);
  }

  /**
   * Deletes all stored data.
   */
  public function deleteAllData() {
    \Drupal::state()->deleteMultiple(array(
      'acquia_connector.key',
      'acquia_connector.identifier'
    ));
  }

}
