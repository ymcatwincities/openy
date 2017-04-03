<?php

namespace Drupal\openy_facebook_sync;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Facebook\Facebook;

/**
 * Class OpenyFacebookSyncFactory.
 *
 * Creates an instance of Facebook\Facebook service with app ID and secret from
 * module settings.
 */
class OpenyFacebookSyncFactory {

  protected $configFactory;
  protected $loggerFactory;
  protected $dataHandler;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   Used for accessing Drupal configuration.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   Used for logging errors.
   * @param \Drupal\openy_facebook_sync\OpenyFacebookSyncDataHandler $data_handler
   *   Used for reading data from and writing data to session.
   */
  public function __construct(ConfigFactoryInterface $config_factory, LoggerChannelFactoryInterface $logger_factory, OpenyFacebookSyncDataHandler $data_handler) {
    $this->configFactory = $config_factory;
    $this->loggerFactory = $logger_factory;
    $this->dataHandler = $data_handler;
  }

  /**
   * Returns an instance of Facebook\Facebook service.
   *
   * Reads Facebook App ID and App Secret from module settings
   * and creates an instance of Facebook service with these as parameters.
   *
   * @return \Facebook\Facebook | bool
   *   Facebook service instance.
   */
  public function getFacebook() {
    // Check that App ID and secret have been defined in module settings.
    if ($this->validateConfig()) {
      $sdk_config = array(
        'app_id' => $this->getAppId(),
        'app_secret' => $this->getAppSecret(),
        'default_graph_version' => $this->getApiVersion(),
        'persistent_data_handler' => $this->persistentDataHandler,
        'http_client_handler' => $this->getHttpClient(),
      );
      return new Facebook($sdk_config);
    }

    // Return FALSE if app ID or secret is missing.
    return FALSE;
  }

  /**
   * Returns an instance of dataHandler service.
   *
   * @return \Drupal\openy_facebook_sync\OpenyFacebookSyncDataHandler
   *   Service instance.
   */
  public function getDataHandler() {
    return $this->dataHandler;
  }

  /**
   * Checks that module is configured.
   *
   * @return bool
   *   True if module is configured
   *   False otherwise
   */
  protected function validateConfig() {
    $app_id = $this->getAppId();
    $app_secret = $this->getAppSecret();

    if (!$app_id || !$app_secret) {
      $this->loggerFactory
        ->get('openy_facebook_sync')
        ->error('Define App ID and App Secret on module settings.');
      return FALSE;
    }

    return TRUE;
  }

  /**
   * Returns app_id from module settings.
   *
   * @return string
   *   Application ID defined in module settings.
   */
  protected function getAppId() {
    $app_id = $this->configFactory
      ->get('openy_facebook_sync.settings')
      ->get('app_id');
    return $app_id;
  }

  /**
   * Returns app_secret from module settings.
   *
   * @return string
   *   Application secret defined in module settings.
   */
  protected function getAppSecret() {
    $app_secret = $this->configFactory
      ->get('openy_facebook_sync.settings')
      ->get('app_secret');
    return $app_secret;
  }

  /**
   * Returns api_version from module settings.
   *
   * @return string
   *   API version defined in module settings.
   */
  protected function getApiVersion() {
    $api_version = $this->configFactory
      ->get('openy_facebook_sync.settings')
      ->get('api_version');
    return $api_version;
  }

  /**
   * Returns HTTP client to be used with Facebook SDK.
   *
   * Facebook SDK v5 uses the following autodetect logic for determining the
   * HTTP client:
   * 1. If cURL extension is loaded, use it.
   * 2. If cURL was not loaded but Guzzle is found, use it.
   * 3. Fallback to FacebookStreamHttpClient.
   *
   * Drupal 8 ships with Guzzle v6 but Facebook SDK v5 works only
   * with Guzzle v5. Therefore we need to change the autodetect logic
   * so that we're first using cURL and if that is not available, we
   * fallback directly to FacebookStreamHttpClient.
   *
   * @return string
   *   Client that should be used with Facebook SDK.
   */
  protected function getHttpClient() {
    if (extension_loaded('curl')) {
      return 'curl';
    }
    return 'stream';
  }

}
