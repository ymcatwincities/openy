<?php

namespace Drupal\acquia_connector\Controller;

use Drupal\acquia_connector\Helper\Storage;
use Drupal\acquia_connector\Subscription;
use Drupal\acquia_connector\CryptConnector;
use Drupal\Core\Access\AccessResultAllowed;
use Drupal\Core\Access\AccessResultForbidden;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class ModuleDataController.
 *
 * @package Drupal\acquia_connector\Controller
 */
class ModuleDataController extends ControllerBase {

  /**
   * Send a file's contents to the requestor.
   */
  public function sendModuleData($data = array()) {
    $request = \Drupal::request();
    if (empty($data)) {
      $data = json_decode($request->getContent(), TRUE);
    }

    $headers = [
      'Expires' => 'Mon, 26 Jul 1997 05:00:00 GMT',
      'Content-Type' => 'text/plain',
      'Cache-Control' => 'no-cache',
      'Pragma' => 'no-cache',
    ];

    // If our checks pass muster, then we'll provide this data.
    // If the file variable is set and if the user has allowed file diffing.
    $file = $data['body']['file'];
    $document_root = getcwd();
    $file_path = realpath($document_root . '/' . $file);
    // Be sure the file being requested is within the webroot and is not any
    // settings.php file.
    if (is_file($file_path) && strpos($file_path, $document_root) === 0 && strpos($file_path, 'settings.php') === FALSE) {
      $file_contents = file_get_contents($file_path);

      return new Response($file_contents, Response::HTTP_OK, $headers);
    }
    return new Response('', Response::HTTP_NOT_FOUND, $headers);
  }

  /**
   * Validate request.
   *
   * @param array $data
   *   Data array.
   * @param string $message
   *   Data string.
   *
   * @return bool
   *   TRUE if request is valid, FALSE otherwise.
   */
  public function isValidRequest($data, $message) {
    $storage = new Storage();
    $key = $storage->getKey();
    if (!isset($data['authenticator']) || !isset($data['authenticator']['time']) || !isset($data['authenticator']['nonce'])) {
      return FALSE;
    }
    $string = $data['authenticator']['time'] . ':' . $data['authenticator']['nonce'] . ':' . $message;
    $hash = CryptConnector::acquiaHash($key, $string);
    if ($hash == $data['authenticator']['hash']) {
      return TRUE;
    }
    else {
      return FALSE;
    }
  }

  /**
   * Access callback for sendModuleData() callback.
   */
  public function access() {
    $request = \Drupal::request();
    $data = json_decode($request->getContent(), TRUE);

    // We only do this if we are on SSL.
    $via_ssl = $request->isSecure();
    if ($this->config('acquia_connector.settings')->get('spi.ssl_override')) {
      $via_ssl = TRUE;
    }

    if ($this->config('acquia_connector.settings')->get('spi.module_diff_data') && $via_ssl) {
      $subscription = new Subscription();
      if ($subscription->hasCredentials() && isset($data['body']['file']) && $this->isValidRequest($data, $data['body']['file'])) {
        return AccessResultAllowed::allowed();
      }

      // Log the request if validation failed and debug is enabled.
      if ($this->config('acquia_connector.settings')->get('debug')) {
        $info = array(
          'data' => $data,
          'get' => $request->query->all(),
          'server' => $request->server->all(),
          'request' => $request->request->all(),
        );

        \Drupal::logger('acquia module data')->notice('Site Module Data request: @data', array('@data' => var_export($info, TRUE)));
      }
    }

    return AccessResultForbidden::forbidden();
  }

}
