<?php

namespace Drupal\acquia_connector\Controller;

use Drupal\acquia_connector\Subscription;
use Drupal\Core\Access\AccessResultAllowed;
use Drupal\Core\Access\AccessResultForbidden;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Component\Utility\UrlHelper;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Class StatusController.
 */
class StatusController extends ControllerBase {

  /**
   * Menu callback for 'admin/config/system/acquia-agent/refresh-status'.
   */
  public function refresh() {
    // Refresh subscription information, so we are sure about our update status.
    // We send a heartbeat here so that all of our status information gets
    // updated locally via the return data.
    $subscription = new Subscription();
    $subscription->update();

    // Return to the setting pages (or destination).
    return $this->redirect('system.status');
  }

  /**
   * Return JSON site status.
   *
   * Used by Acquia uptime monitoring.
   */
  public function json() {
    // We don't want this page cached.
    \Drupal::service('page_cache_kill_switch')->trigger();

    $performance_config = $this->config('system.performance');

    $data = array(
      'version' => '1.0',
      'data' => array(
        'maintenance_mode' => (bool) $this->state()->get('system.maintenance_mode'),
        'cache' => $performance_config->get('cache.page.use_internal'),
        'block_cache' => FALSE,
      ),
    );

    return new JsonResponse($data);
  }

  /**
   * Access callback for json() callback.
   */
  public function access() {
    $request = \Drupal::request();
    $nonce = $request->get('nonce', FALSE);
    $connector_config = $this->config('acquia_connector.settings');

    // If we don't have all the query params, leave now.
    if (!$nonce) {
      return AccessResultForbidden::forbidden();
    }

    $sub_data = $connector_config->get('subscription_data');
    $sub_uuid = $this->getIdFromSub($sub_data);

    if (!empty($sub_uuid)) {
      $expected_hash = hash('sha1', "{$sub_uuid}:{$nonce}");

      // If the generated hash matches the hash from $_GET['key'], we're good.
      if ($request->get('key', FALSE) === $expected_hash) {
        return AccessResultAllowed::allowed();
      }
    }

    // Log the request if validation failed and debug is enabled.
    if ($connector_config->get('debug')) {
      $info = array(
        'sub_data' => $sub_data,
        'sub_uuid_from_data' => $sub_uuid,
        'expected_hash' => $expected_hash,
        'get' => $request->query->all(),
        'server' => $request->server->all(),
        'request' => $request->request->all(),
      );

      \Drupal::logger('acquia_agent')->notice('Site status request: @data', array('@data' => var_export($info, TRUE)));
    }

    return AccessResultForbidden::forbidden();
  }

  /**
   * Gets the subscription UUID from subscription data.
   *
   * @param array $sub_data
   *   An array of subscription data.
   *
   * @see acquia_agent_settings('acquia_subscription_data')
   *
   * @return string
   *   The UUID taken from the subscription data.
   */
  public function getIdFromSub($sub_data) {
    if (!empty($sub_data['uuid'])) {
      return $sub_data['uuid'];
    }

    // Otherwise, get this form the sub url.
    $url = UrlHelper::parse($sub_data['href']);
    $parts = explode('/', $url['path']);
    // Remove '/dashboard'.
    array_pop($parts);

    return end($parts);
  }

}
