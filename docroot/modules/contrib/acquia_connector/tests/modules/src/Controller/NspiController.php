<?php

namespace Drupal\acquia_connector_test\Controller;

use Drupal\Core\Access\AccessResultAllowed;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\acquia_connector\CryptConnector;

/**
 * Class NspiController.
 *
 * @package Drupal\acquia_connector_test\Controller
 */
class NspiController extends ControllerBase {

  protected $data = [];
  protected $acqtestSiteMachineName;
  protected $acquiaHosted;

  const ACQTEST_SUBSCRIPTION_NOT_FOUND = 1000;
  const ACQTEST_SUBSCRIPTION_KEY_MISMATCH = 1100;
  const ACQTEST_SUBSCRIPTION_EXPIRED = 1200;
  const ACQTEST_SUBSCRIPTION_REPLAY_ATTACK = 1300;
  const ACQTEST_SUBSCRIPTION_KEY_NOT_FOUND = 1400;
  const ACQTEST_SUBSCRIPTION_MESSAGE_FUTURE = 1500;
  const ACQTEST_SUBSCRIPTION_MESSAGE_EXPIRED = 1600;
  const ACQTEST_SUBSCRIPTION_MESSAGE_INVALID = 700;
  const ACQTEST_SUBSCRIPTION_VALIDATION_ERROR = 1800;
  const ACQTEST_SUBSCRIPTION_SITE_NOT_FOUND = 1900;
  const ACQTEST_SUBSCRIPTION_PROVISION_ERROR = 9000;
  // 15*60.
  const ACQTEST_SUBSCRIPTION_MESSAGE_LIFETIME = 900;
  const ACQTEST_SUBSCRIPTION_SERVICE_UNAVAILABLE = 503;
  const ACQTEST_EMAIL = 'TEST_networkuser@example.com';
  const ACQTEST_PASS = 'TEST_password';
  const ACQTEST_ID = 'TEST_AcquiaConnectorTestID';
  const ACQTEST_KEY = 'TEST_AcquiaConnectorTestKey';
  const ACQTEST_ERROR_ID = 'TEST_AcquiaConnectorTestIDErr';
  const ACQTEST_ERROR_KEY = 'TEST_AcquiaConnectorTestKeyErr';
  const ACQTEST_EXPIRED_ID = 'TEST_AcquiaConnectorTestIDExp';
  const ACQTEST_EXPIRED_KEY = 'TEST_AcquiaConnectorTestKeyExp';
  const ACQTEST_503_ID = 'TEST_AcquiaConnectorTestID503';
  const ACQTEST_503_KEY = 'TEST_AcquiaConnectorTestKey503';
  const ACQTEST_SITE_UUID = 'TEST_cdbd59f5-ca7e-4652-989b-f9e46d309613';
  const ACQTEST_UUID = 'cdbd59f5-ca7e-4652-989b-f9e46d312458';

  /**
   * Construction method.
   */
  public function __construct() {
    $this->acqtestSiteMachineName = \Drupal::state()->get('acqtest_site_machine_name');
    $this->acquiaHosted = \Drupal::state()->get('acqtest_site_acquia_hosted');
  }

  /**
   * SPI API site update.
   *
   * @param Request $request
   *   Request.
   *
   * @return JsonResponse
   *   JsonResponse.
   */
  public function nspiUpdate(Request $request) {
    $data = json_decode($request->getContent(), TRUE);

    $fields = [
      'time' => 'is_numeric',
      'nonce' => 'is_string',
      'hash' => 'is_string',
    ];
    $result = $this->basicAuthenticator($fields, $data);

    if (!empty($result['error'])) {
      return new JsonResponse($result);
    }
    if (!empty($data['authenticator']['identifier'])) {
      if ($data['authenticator']['identifier'] != self::ACQTEST_ID && $data['authenticator']['identifier'] != self::ACQTEST_ERROR_ID) {
        return new JsonResponse($this->errorResponse(self::ACQTEST_SUBSCRIPTION_VALIDATION_ERROR, t('Subscription not found')), self::ACQTEST_SUBSCRIPTION_SERVICE_UNAVAILABLE);
      }
      if ($data['authenticator']['identifier'] == self::ACQTEST_ERROR_ID) {
        return new JsonResponse(FALSE);
      }
      else {
        $result = $this->validateAuthenticator($data);
        // Needs for update definition.
        $data['body']['spi_def_update'] = TRUE;
        $spi_data = $data['body'];

        $result['body'] = ['spi_data_received' => TRUE];
        if (isset($spi_data['spi_def_update'])) {
          $result['body']['update_spi_definition'] = TRUE;
        }

        // Reflect send_method as nspi_messages if set.
        if (isset($spi_data['send_method'])) {
          $result['body']['nspi_messages'][] = $spi_data['send_method'];
        }
        $result['authenticator']['hash'] = CryptConnector::acquiaHash($result['secret']['key'], $result['authenticator']['time'] . ':' . $result['authenticator']['nonce']);
        if (isset($spi_data['test_validation_error'])) {
          // Force a validation fail.
          $result['authenticator']['nonce'] = 'TEST';
        }

        $site_action = $spi_data['env_changed_action'];
        // First connection.
        if (empty($spi_data['site_uuid'])) {
          $site_action = 'create';
        }

        switch ($site_action) {
          case 'create':
            $result['body']['site_uuid'] = self::ACQTEST_SITE_UUID;
            // Set machine name.
            \Drupal::state()->set('acqtest_site_machine_name', $spi_data['machine_name']);
            // Set name.
            \Drupal::state()->set('acqtest_site_name', $spi_data['name']);
            $acquia_hosted = (int) filter_var($spi_data['acquia_hosted'], FILTER_VALIDATE_BOOLEAN);
            \Drupal::state()->set('acqtest_site_acquia_hosted', $acquia_hosted);

            $result['body']['nspi_messages'][] = t('This is the first connection from this site, it may take awhile for it to appear on the Acquia Network.');
            return new JsonResponse($result);

          break;
          case 'update':
            $update = $this->updateNspiSite($spi_data);
            $result['body']['nspi_messages'][] = $update;
            break;

          case 'unblock':
            \Drupal::state()->delete('acqtest_site_blocked');
            $result['body']['spi_error'] = '';
            $result['body']['nspi_messages'][] = t('Your site has been unblocked and is sending data to Acquia Cloud.');
            return new JsonResponse($result);

          break;
          case 'block':
            \Drupal::state()->set('acqtest_site_blocked', TRUE);
            $result['body']['spi_error'] = '';
            $result['body']['nspi_messages'][] = t('You have blocked your site from sending data to Acquia Cloud.');
            return new JsonResponse($result);

          break;
        }

        // Update site name if it has changed.
        $tacqtest_site_name = \Drupal::state()->get('acqtest_site_name');
        if (isset($spi_data['name']) && $spi_data['name'] != $tacqtest_site_name) {
          if (!empty($tacqtest_site_name)) {
            $name_update_message = t('Site name updated (from @old_name to @new_name).', [
              '@old_name' => $tacqtest_site_name,
              '@new_name' => $spi_data['name'],
            ]);

            \Drupal::state()->set('acqtest_site_name', $spi_data['name']);
          }
          $result['body']['nspi_messages'][] = $name_update_message;
        }

        // Detect Changes.
        if ($changes = $this->detectChanges($spi_data)) {
          $result['body']['nspi_messages'][] = $changes['response'];
          $result['body']['spi_error'] = TRUE;
          $result['body']['spi_environment_changes'] = json_encode($changes['changes']);
          return new JsonResponse($result);
        }

        unset($result['secret']);
        return new JsonResponse($result);
      }
    }
    else {
      return new JsonResponse($this->errorResponse(self::ACQTEST_SUBSCRIPTION_VALIDATION_ERROR, t('Invalid arguments')), self::ACQTEST_SUBSCRIPTION_SERVICE_UNAVAILABLE);
    }
  }

  /**
   * Detect potential environment changes.
   *
   * @param array $spi_data
   *   SPI data array.
   *
   * @return array|bool
   *   FALSE or changes message.
   */
  public function detectChanges(array $spi_data) {
    $changes = [];
    $site_blocked = \Drupal::state()->get('acqtest_site_blocked');

    if ($site_blocked) {
      $changes['changes']['blocked'] = (string) t('Your site has been unblocked.');
    }
    else {

      if ($this->checkAcquiaHostedStatusChanged($spi_data) && !is_null($this->acquiaHosted)) {
        if ($spi_data['acquia_hosted']) {
          $changes['changes']['acquia_hosted'] = (string) t('Your site is now Acquia hosted.');
        }
        else {
          $changes['changes']['acquia_hosted'] = (string) t('Your site is no longer Acquia hosted.');
        }
      }

      if ($this->checkMachineNameStatusChanged($spi_data)) {
        $changes['changes']['machine_name'] = (string) t('Your site machine name changed from @old_machine_name to @new_machine_name.', [
          '@old_machine_name' => $this->acqtestSiteMachineName,
          '@new_machine_name' => $spi_data['machine_name'],
        ]);
      }

    }

    if (empty($changes)) {
      return FALSE;
    }

    $changes['response'] = (string) t('A change has been detected in your site environment. Please check the Acquia SPI status on your Status Report page for more information.');

    return $changes;
  }

  /**
   * Save changes to the site entity.
   *
   * @param array $spi_data
   *   SPI data array.
   *
   * @return string
   *   Message string.
   */
  public function updateNspiSite(array $spi_data) {
    $message = '';

    if ($this->checkMachineNameStatusChanged($spi_data)) {
      if (!empty($this->acqtestSiteMachineName)) {
        $message = (string) t('Updated site machine name from @old_machine_name to @new_machine_name.', ['@old_machine_name' => $this->acqtestSiteMachineName, '@new_machine_name' => $spi_data['machine_name']]);
      }
      else {
        $message  = (string) t('Site machine name set to to @new_machine_name.', ['@new_machine_name' => $spi_data['machine_name']]);
      }

      \Drupal::state()->set('acqtest_site_machine_name', $spi_data['machine_name']);
      $this->acqtestSiteMachineName = $spi_data['machine_name'];
    }

    if ($this->checkAcquiaHostedStatusChanged($spi_data)) {
      if (!is_null($this->acquiaHosted)) {
        $hosted_message = $spi_data['acquia_hosted'] ? (string) t('site is now Acquia hosted') : (string) t('site is no longer Acquia hosted');
        $message = (string) t('Updated Acquia hosted status (@hosted_message).', ['@hosted_message' => $hosted_message]);
      }

      $acquia_hosted = (int) filter_var($spi_data['acquia_hosted'], FILTER_VALIDATE_BOOLEAN);
      \Drupal::state()->set('acqtest_site_acquia_hosted', $acquia_hosted);
      $this->acquiaHosted = $acquia_hosted;
    }

    return $message;
  }

  /**
   * Detect if machine name changed.
   *
   * @param array $spi_data
   *   SPI data.
   *
   * @return bool
   *   TRUE if machine name was changed.
   */
  public function checkMachineNameStatusChanged($spi_data) {
    return isset($spi_data['machine_name']) && $spi_data['machine_name'] != $this->acqtestSiteMachineName;
  }

  /**
   * Detect if Acquia hosted changed.
   *
   * @param array $spi_data
   *   SPI data.
   *
   * @return bool
   *   TRUE if site is Acquia Hosted.
   */
  public function checkAcquiaHostedStatusChanged($spi_data) {
    return isset($spi_data['acquia_hosted']) && (bool) $spi_data['acquia_hosted'] != (bool) $this->acquiaHosted;
  }

  /**
   * Return spi definition.
   *
   * @param Request $request
   *   Request.
   * @param string $version
   *   Version.
   *
   * @return JsonResponse
   *   JsonResponse.
   */
  public function spiDefinition(Request $request, $version) {
    $vars = [
      'test_variable_1' => [
        'optional' => FALSE,
        'description' => 'test_variable_1',
      ],
      'test_variable_2' => [
        'optional' => TRUE,
        'description' => 'test_variable_2',
      ],
      'test_variable_3' => [
        'optional' => TRUE,
        'description' => 'test_variable_3',
      ],
    ];
    $data = [
      'drupal_version' => (string) $version,
      'timestamp' => (string) (REQUEST_TIME + 9),
      'acquia_spi_variables' => $vars,
    ];
    return new JsonResponse($data);
  }

  /**
   * Test return communication settings for an account.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   Request.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   JsonResponse.
   */
  public function getCommunicationSettings(Request $request) {
    $data = json_decode($request->getContent(), TRUE);
    $fields = [
      'time' => 'is_numeric',
      'nonce' => 'is_string',
      'hash' => 'is_string',
    ];

    // Authenticate.
    $result = $this->basicAuthenticator($fields, $data);
    if (!empty($result['error'])) {
      return new JsonResponse($result);
    }

    if (!isset($data['body']) || !isset($data['body']['email'])) {
      return new JsonResponse($this->errorResponse(self::ACQTEST_SUBSCRIPTION_VALIDATION_ERROR, t('Invalid arguments')), self::ACQTEST_SUBSCRIPTION_SERVICE_UNAVAILABLE);
    }
    $account = user_load_by_mail($data['body']['email']);
    if (empty($account) || $account->isAnonymous()) {
      return new JsonResponse($this->errorResponse(self::ACQTEST_SUBSCRIPTION_VALIDATION_ERROR, t('Account not found')), self::ACQTEST_SUBSCRIPTION_SERVICE_UNAVAILABLE);
    }
    $result = [
      'algorithm' => 'sha512',
      'hash_setting' => substr($account->getPassword(), 0, 12),
      'extra_md5' => FALSE,
    ];
    return new JsonResponse($result);
  }

  /**
   * Basic authenticator.
   *
   * @param array $fields
   *   Fields array.
   * @param array $data
   *   Data array.
   *
   * @return array
   *   Result array.
   */
  protected function basicAuthenticator($fields, $data) {
    $result = [];
    foreach ($fields as $field => $type) {
      if (empty($data['authenticator'][$field]) || !$type($data['authenticator'][$field])) {
        return $this->errorResponse(self::ACQTEST_SUBSCRIPTION_MESSAGE_INVALID, t('Authenticator field @field is missing or invalid.', ['@field' => $field]));
      }
    }
    $now = REQUEST_TIME;
    if ($data['authenticator']['time'] > ($now + self::ACQTEST_SUBSCRIPTION_MESSAGE_LIFETIME)) {
      return $this->errorResponse(self::ACQTEST_SUBSCRIPTION_MESSAGE_FUTURE, t('Message time ahead of server time.'));
    }
    else {
      if ($data['authenticator']['time'] < ($now - self::ACQTEST_SUBSCRIPTION_MESSAGE_LIFETIME)) {
        return $this->errorResponse(self::ACQTEST_SUBSCRIPTION_MESSAGE_EXPIRED, t('Message is too old.'));
      }
    }

    $result['error'] = FALSE;
    return $result;
  }

  /**
   * Test returns subscriptions for an email.
   *
   * @param Request $request
   *   Request.
   *
   * @return JsonResponse
   *   JsonResponse.
   */
  public function getCredentials(Request $request) {
    $data = json_decode($request->getContent(), TRUE);

    $fields = [
      'time' => 'is_numeric',
      'nonce' => 'is_string',
      'hash' => 'is_string',
    ];
    $result = $this->basicAuthenticator($fields, $data);
    if (!empty($result['error'])) {
      return new JsonResponse($result, self::ACQTEST_SUBSCRIPTION_SERVICE_UNAVAILABLE);
    }

    if (!empty($data['body']['email'])) {
      $account = user_load_by_mail($data['body']['email']);
      \Drupal::logger('getCredentials password')->debug($account->getPassword());
      if (empty($account) || $account->isAnonymous()) {
        return new JsonResponse($this->errorResponse(self::ACQTEST_SUBSCRIPTION_VALIDATION_ERROR, t('Account not found')), self::ACQTEST_SUBSCRIPTION_SERVICE_UNAVAILABLE);
      }
    }
    else {
      return new JsonResponse($this->errorResponse(self::ACQTEST_SUBSCRIPTION_VALIDATION_ERROR, t('Invalid arguments')), self::ACQTEST_SUBSCRIPTION_SERVICE_UNAVAILABLE);
    }

    $hash = CryptConnector::acquiaHash($account->getPassword(), $data['authenticator']['time'] . ':' . $data['authenticator']['nonce']);
    if ($hash === $data['authenticator']['hash']) {
      $result = [];
      $result['is_error'] = FALSE;
      $result['body']['subscription'][] = [
        'identifier' => self::ACQTEST_ID,
        'key' => self::ACQTEST_KEY,
        'name' => self::ACQTEST_ID,
      ];
      return new JsonResponse($result);
    }
    else {
      return new JsonResponse($this->errorResponse(self::ACQTEST_SUBSCRIPTION_VALIDATION_ERROR, t('Incorrect password.')), self::ACQTEST_SUBSCRIPTION_SERVICE_UNAVAILABLE);
    }
  }

  /**
   * Test validates an Acquia Network subscription.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   Request.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   JsonResponse.
   */
  public function getSubscription(Request $request) {
    $data = json_decode($request->getContent(), TRUE);
    $result = $this->validateAuthenticator($data);
    if (empty($result['error'])) {
      $result['authenticator']['hash'] = CryptConnector::acquiaHash($result['secret']['key'], $result['authenticator']['time'] . ':' . $result['authenticator']['nonce']);
      unset($result['secret']);
      return new JsonResponse($result);
    }
    unset($result['secret']);
    return new JsonResponse($result, self::ACQTEST_SUBSCRIPTION_SERVICE_UNAVAILABLE);
  }

  /**
   * Test validates an Acquia Network authenticator.
   *
   * @param array $data
   *   Data to validate.
   *
   * @return array
   *   Result array.
   */
  protected function validateAuthenticator($data) {
    $fields = [
      'time' => 'is_numeric',
      'identifier' => 'is_string',
      'nonce' => 'is_string',
      'hash' => 'is_string',
    ];

    $result = $this->basicAuthenticator($fields, $data);
    if (!empty($result['error'])) {
      return $result;
    }

    if (strpos($data['authenticator']['identifier'], 'TEST_') !== 0) {
      return $this->errorResponse(self::ACQTEST_SUBSCRIPTION_NOT_FOUND, t('Subscription not found'));
    }

    switch ($data['authenticator']['identifier']) {
      case self::ACQTEST_ID:
        $key = self::ACQTEST_KEY;
        break;

      case self::ACQTEST_EXPIRED_ID:
        $key = self::ACQTEST_EXPIRED_KEY;
        break;

      case self::ACQTEST_503_ID:
        $key = self::ACQTEST_503_KEY;
        break;

      default:
        $key = self::ACQTEST_ERROR_KEY;
        break;
    }

    $hash = CryptConnector::acquiaHash($key, $data['authenticator']['time'] . ':' . $data['authenticator']['nonce']);
    $hash_simple = CryptConnector::acquiaHash($key, $data['authenticator']['time'] . ':' . $data['authenticator']['nonce']);

    if (($hash !== $data['authenticator']['hash']) && ($hash_simple != $data['authenticator']['hash'])) {
      return $this->errorResponse(self::ACQTEST_SUBSCRIPTION_VALIDATION_ERROR, t('HMAC validation error: @expected != @actual'), [
        '@expected' => $hash,
        '@actual' => $data['authenticator']['hash'],
      ]);
    }

    if ($key === self::ACQTEST_EXPIRED_KEY) {
      return $this->errorResponse(self::ACQTEST_SUBSCRIPTION_EXPIRED, t('Subscription expired.'));
    }

    // Record connections.
    $connections = \Drupal::config('acquia_connector.settings')->get('test_connections' . $data['authenticator']['identifier']);
    $connections++;
    \Drupal::configFactory()->getEditable('acquia_connector.settings')->set('test_connections' . $data['authenticator']['identifier'], $connections)->save();
    if ($connections == 3 && $data['authenticator']['identifier'] == self::ACQTEST_503_ID) {
      // Trigger a 503 response on 3rd call to this (1st is
      // acquia.agent.subscription and 2nd is acquia.agent.validate)
      return $this->errorResponse(self::ACQTEST_SUBSCRIPTION_SERVICE_UNAVAILABLE, 'Subscription service unavailable.');
    }
    $result['error'] = FALSE;
    $result['body']['subscription_name'] = 'TEST_AcquiaConnectorTestID';
    $result['body']['active'] = 1;
    $result['body']['href'] = 'http://acquia.com/network';
    $result['body']['expiration_date']['value'] = '2023-10-08T06:30:00';
    $result['body']['product'] = '91990';
    $result['body']['derived_key_salt'] = $data['authenticator']['identifier'] . '_KEY_SALT';
    $result['body']['update_service'] = 1;
    $result['body']['search_service_enabled'] = 1;
    $result['body']['uuid'] = self::ACQTEST_UUID;
    if (isset($data['body']['rpc_version'])) {
      $result['body']['rpc_version'] = $data['body']['rpc_version'];
    }
    $result['secret']['data'] = $data;
    $result['secret']['nid'] = '91990';
    $result['secret']['node'] = $data['authenticator']['identifier'] . '_NODE';
    $result['secret']['key'] = $key;
    // $result['secret']['nonce'] = '';.
    $result['authenticator'] = $data['authenticator'];
    $result['authenticator']['hash'] = '';
    $result['authenticator']['time'] += 1;
    $result['authenticator']['nonce'] = $data['authenticator']['nonce'];
    return $result;
  }

  /**
   * Test returns environments available for site import.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   Request.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   JsonResponse.
   */
  public function cloudMigrationEnvironments(Request $request) {
    $data = json_decode($request->getContent(), TRUE);

    global $base_url;
    $fields = [
      'time' => 'is_numeric',
      'nonce' => 'is_string',
      'hash' => 'is_string',
    ];
    $result = $this->basicAuthenticator($fields, $data);
    if (!empty($result['error'])) {
      return new JsonResponse($result, self::ACQTEST_SUBSCRIPTION_SERVICE_UNAVAILABLE);
    }
    if (!empty($data['body']['identifier'])) {
      if (strpos($data['body']['identifier'], 'TEST_') !== 0) {
        return new JsonResponse($this->errorResponse(self::ACQTEST_SUBSCRIPTION_VALIDATION_ERROR, t('Subscription not found')), self::ACQTEST_SUBSCRIPTION_SERVICE_UNAVAILABLE);
      }
    }
    else {
      return new JsonResponse($this->errorResponse(self::ACQTEST_SUBSCRIPTION_VALIDATION_ERROR, t('Invalid arguments')), self::ACQTEST_SUBSCRIPTION_SERVICE_UNAVAILABLE);
    }
    if ($data['body']['identifier'] == self::ACQTEST_ERROR_ID) {
      return new JsonResponse($this->errorResponse(self::ACQTEST_SUBSCRIPTION_SITE_NOT_FOUND, t("Hosting not available under your subscription. Upgrade your subscription to continue with import.")), self::ACQTEST_SUBSCRIPTION_SERVICE_UNAVAILABLE);
    }
    $result = [];
    $result['is_error'] = FALSE;
    foreach (['dev' => 'Development', 'test' => 'Stage', 'prod' => 'Production'] as $key => $name) {
      $result['body']['environments'][$key] = [
        'url' => $base_url . '/system/acquia-connector-test-upload/AH_UPLOAD',
        'stage' => $key,
        'nonce' => 'nonce',
        'secret' => 'secret',
        'site_name' => $name,
      ];
    }
    return new JsonResponse($result);
  }

  /**
   * Test migration upload.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   Request.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\Response
   *   Invalid request Response or JsonResponse.
   */
  public function testMigrationUpload(Request $request) {
    $server_to_fail = \Drupal::configFactory()->getEditable('acquia_connector.settings')->get('acquia_connector_test_upload_server_to_fail');
    if ($server_to_fail) {
      $data = [
        'network_url' => 'site.acquia.dev',
        'success' => TRUE,
        'error' => FALSE,
        'sig' => 'rh4gr4@%#^fnreg',
      ];
      return new JsonResponse($data, Response::HTTP_INTERNAL_SERVER_ERROR);
    }

    return new Response('invalid request', Response::HTTP_BAD_REQUEST);
  }

  /**
   * Test complete final migration.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   Request.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   Json response.
   */
  public function testMigrationComplete(Request $request) {
    $data = [
      'network_url' => 'site.acquia.dev',
      'success' => TRUE,
      'error' => FALSE,
    ];
    return new JsonResponse($data);
  }

  /**
   * Access callback.
   *
   * @return bool
   *   TRUE if access is allowed.
   */
  public function access() {
    return AccessResultAllowed::allowed();
  }

  /**
   * Format the error response.
   *
   * @param mixed $code
   *   Error code.
   * @param string $message
   *   Error message.
   *
   * @return array
   *   Error response.
   */
  protected function errorResponse($code, $message) {
    return [
      'code' => $code,
      'message' => $message,
      'error' => TRUE,
    ];
  }

}
