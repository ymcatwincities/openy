<?php

namespace Drupal\ymca_google;

use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * Class YmcaGoogleTestManager.
 *
 * @package Drupal\ymca_google
 */
class YmcaGoogleTestManager {

  /**
   * Config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * YmcaGoogleTestManager constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   Config factory.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->configFactory = $config_factory;
  }

  /**
   * Set the service to test mode.
   */
  public function setTestMode() {
    // Configure settings.
    $editable = $this->configFactory->getEditable('ymca_google.settings');
    $editable->set('application_name', 'groupx-to-gcal-sync-test');
    $editable->set('is_production', 0);
    $auth_config = [
      'installed' => [
        'client_id' => '509881666221-4rhnvlr4j1iloncjka81dr2koil03arn.apps.googleusercontent.com',
        'project_id' => 'norse-glow-143510',
        'auth_uri' => 'https://accounts.google.com/o/oauth2/auth',
        'token_uri' => 'https://accounts.google.com/o/oauth2/token',
        'auth_provider_x509_cert_url' => 'https://www.googleapis.com/oauth2/v1/certs',
        'client_secret' => '33UM0B96dv0g63uVBk7sAkWS',
        'redirect_uris' => ['urn:ietf:wg:oauth:2.0:oob', 'http://localhost'],
      ],
    ];
    $editable->set('auth_config', $auth_config);
    $editable->save();

    // Configure token.
    $editable = $this->configFactory->getEditable('ymca_google.token');
    $credentials = [
      'access_token' => 'ya29.Ci9jA5vt_9118JlGNb3sMqKB8B7170D_q60116RS6Ry9B7UmYJW2N97HxUQwX5rzDA',
      'token_type' => 'Bearer',
      'expires_in' => 3600,
      'refresh_token' => '1\/lFJm_hgMRDvgXr0A8ti3Ji6XekuWliG_OkN3UjqovdA',
      'created' => 1474275216,
    ];
    $editable->set('credentials', $credentials);
    $editable->save();
  }

}
