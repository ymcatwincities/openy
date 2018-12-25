<?php

namespace Drupal\acquia_connector;

use Drupal\acquia_connector\Helper\Storage;

/**
 * Class Subscription.
 *
 * @package Drupal\acquia_connector.
 */
class Subscription {

  /**
   * Errors defined by the Acquia Network.
   */
  const NOT_FOUND = 1000;
  const KEY_MISMATCH = 1100;
  const EXPIRED = 1200;
  const REPLAY_ATTACK = 1300;
  const KEY_NOT_FOUND = 1400;
  const MESSAGE_FUTURE = 1500;
  const MESSAGE_EXPIRED = 1600;
  const MESSAGE_INVALID = 1700;
  const VALIDATION_ERROR = 1800;
  const PROVISION_ERROR = 9000;

  /**
   * Subscription message lifetime defined by the Acquia Network.
   */
  // 15 * 60.
  const MESSAGE_LIFETIME = 900;

  /**
   * Get subscription status from the Acquia Network, and store the result.
   *
   * This check also sends a heartbeat to the Acquia Network unless
   * $params['no_heartbeat'] == 1.
   *
   * @param array $params
   *   Optional parameters for \Drupal\acquia_connector\Client::getSubscription.
   *
   * @return mixed
   *   FALSE, integer (error number), or subscription data.
   */
  public function update($params = array()) {
    $config = \Drupal::configFactory()->getEditable('acquia_connector.settings');
    $current_subscription = $config->get('subscription_data');
    $subscription = FALSE;

    if (!self::hasCredentials()) {
      // If there is not an identifier or key, delete any old subscription data.
      $config->clear('subscription_data')->set('subscription_data', ['active' => FALSE])->save();
    }
    else {
      // Get our subscription data.
      try {
        $storage = new Storage();
        $key = $storage->getKey();
        $identifier = $storage->getIdentifier();
        $subscription = \Drupal::service('acquia_connector.client')->getSubscription($identifier, $key, $params);
      }
      catch (ConnectorException $e) {
        switch ($e->getCustomMessage('code')) {
          case self::NOT_FOUND:
          case self::EXPIRED:
            // Fall through since these values are stored and used by
            // acquia_search_acquia_subscription_status()
            $subscription = $e->getCustomMessage('code');
            break;

          default:
            // Likely server error (503) or connection timeout (-110) so leave
            // current subscription in place. _acquia_agent_request() logged an
            // error message.
            return $current_subscription;
        }
      }
      if ($subscription) {
        \Drupal::moduleHandler()->invokeAll('acquia_subscription_status', [$subscription]);
        $config->set('subscription_data', $subscription)->save();
      }
    }

    return $subscription;
  }

  /**
   * Helper function to check if an identifier and key exist.
   */
  public function hasCredentials() {
    $storage = new Storage();
    return $storage->getIdentifier() && $storage->getKey();
  }

  /**
   * Helper function to check if the site has an active subscription.
   */
  public function isActive() {
    $active = FALSE;
    // Subscription cannot be active if we have no credentials.
    if (self::hasCredentials()) {
      $config = \Drupal::config('acquia_connector.settings');
      $subscription = $config->get('subscription_data');

      $subscription_timestamp = \Drupal::state()->get('acquia_subscription_data.timestamp');
      // Make sure we have data at least once per day.
      if (isset($subscription_timestamp) && (time() - $subscription_timestamp > 60 * 60 * 24)) {
        try {
          $storage = new Storage();
          $key = $storage->getKey();
          $identifier = $storage->getIdentifier();
          $subscription = \Drupal::service('acquia_connector.client')->getSubscription($identifier, $key, ['no_heartbeat' => 1]);
        }
        catch (ConnectorException $e) {
        }
      }
      $active = !empty($subscription['active']);
    }
    return $active;
  }

}
