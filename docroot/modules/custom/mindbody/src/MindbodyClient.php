<?php

namespace Drupal\mindbody;

use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * Mindbody Service Manager.
 *
 * @package Drupal\mindbody
 */
class MindbodyClient implements MindbodyClientInterface {

  /**
   * Debug mode.
   *
   * @var bool
   */
  public $debug;

  /**
   * Api hostname.
   *
   * @var string
   */
  public $hostname;

  /**
   * Config Factory.
   *
   * @var ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Credentials.
   *
   * @var array
   */
  protected $credentials;

  /**
   * User credentials.
   *
   * @var array
   */
  protected $userCredentials;

  /**
   * Client.
   *
   * @var \SoapClient
   */
  protected $client;

  /**
   * MindbodyServiceManager constructor.
   *
   * @param ConfigFactoryInterface $config_factory
   *   Config factory.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->configFactory = $config_factory;
  }

  /**
   * Set credentials.
   */
  protected function setCredentials() {
    $settings = $this->configFactory->get('mindbody.settings');

    // Check whether the module is configured.
    foreach ($settings->getRawData() as $item_name => $value) {
      if (empty($value)) {
        $message = "Mindbody API credentials are not configured. \"$item_name\" is empty.";
        throw new \Exception($message);
      }
    }

    $this->credentials = [
      'SourceName' => $settings->get('sourcename'),
      'Password' => $settings->get('password'),
      'SiteIDs' => [$settings->get('site_id')],
    ];
  }

  /**
   * Set user credentials.
   */
  protected function setUserCredentials() {
    $settings = $this->configFactory->get('mindbody.settings');

    // Check whether the module is configured.
    foreach ($settings->getRawData() as $item_name => $value) {
      if (empty($value)) {
        $message = "Mindbody API credentials are not configured. \"$item_name\" is empty.";
        throw new \Exception($message);
      }
    }

    // According to documentation we can use credentials, but with underscore at the beginning of username.
    // @see https://developers.mindbodyonline.com/Develop/Authentication.
    $this->userCredentials = [
      'Username' => '_' . $settings->get('sourcename'),
      'Password' => $settings->get('password'),
      'SiteIDs' => [$settings->get('site_id')],
    ];
  }

  /**
   * Set up a client.
   *
   * @param string $service
   *   Service name.
   */
  protected function setUpClient($service) {
    $endpointUrl = "https://" . $this->hostname . "/0_5/" . $service . ".asmx";
    $wsdlUrl = $endpointUrl . "?wsdl";

    $option = [];
    if ($this->debug) {
      $option = [
        'trace' => 1
      ];
    }

    $this->client = new \SoapClient($wsdlUrl, $option);
    $this->client->__setLocation($endpointUrl);
  }

  /**
   * {@inheritdoc}
   */
  public function call($service, $endpoint, array $params = []) {
    $this->setCredentials();
    $this->setUpClient($service);
    if ($params['_SetUserCredentials']) {
      $this->setUserCredentials();
    }
    return $this->client->{$endpoint}($this->getMindbodyParams($params));
  }

  /**
   * Generate params.
   *
   * @param array $additions
   *   Additions.
   *
   * @return array
   *   Params.
   */
  protected function getMindbodyParams(array $additions) {
    $params['SourceCredentials'] = $this->credentials;
    $params['UserCredentials'] = $this->userCredentials;
    $params['XMLDetail'] = 'Full';
    $params['PageSize'] = NULL;
    $params['CurrentPageIndex'] = NULL;
    if (empty($additions['Fields'])) {
      $params['Fields'] = NULL;
    }

    return ['Request' => array_merge($params, $additions)];
  }

}
