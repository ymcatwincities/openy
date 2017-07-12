<?php

namespace Drupal\acquia_connector;

use Drupal\acquia_connector\Helper\Storage;

/**
 * Class AutoConnector.
 *
 * @package Drupal\acquia_connector.
 */
class AutoConnector {

  /**
   * Holds Subscription.
   *
   * @var Subscription
   */
  protected $subscription;

  /**
   * Holds Storage.
   *
   * @var Storage
   */
  protected $storage;

  /**
   * Holds global config.
   *
   * @var array
   */
  protected $globalConfig;

  /**
   * AutoConnector constructor.
   *
   * @param Subscription $subscription
   *   Acquia Subscription.
   * @param Storage $storage
   *   Storage.
   * @param array $global_config
   *   Global config.
   */
  public function __construct(Subscription $subscription, Storage $storage, array $global_config) {
    $this->subscription = $subscription;
    $this->storage = $storage;
    $this->globalConfig = $global_config;
  }

  /**
   * Ensures a connection to Acquia Subscription.
   *
   * @return bool|mixed
   *   False or whatever is returned by Subscription::update.
   */
  public function connectToAcquia() {

    if ($this->subscription->hasCredentials()) {
      return FALSE;
    }

    if (empty($this->globalConfig['ah_network_key'])) {
      return FALSE;
    }

    if (empty($this->globalConfig['ah_network_identifier'])) {
      return FALSE;
    }

    $this->storage->setKey($this->globalConfig['ah_network_key']);
    $this->storage->setIdentifier($this->globalConfig['ah_network_identifier']);

    return $this->subscription->update();

  }

}
