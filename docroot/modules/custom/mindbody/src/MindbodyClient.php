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
  function __construct(ConfigFactoryInterface $config_factory) {
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
  public function call($service, $endpoint, array $params) {
    $this->setCredentials();
    $this->setUpClient($service);
    return $this->client->{$endpoint}($this->GetMindbodyParams($params));
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
  protected function GetMindbodyParams(array $additions) {
    $params['SourceCredentials'] = $this->credentials;
    $params['XMLDetail'] = 'Full';
    $params['PageSize'] = null;
    $params['CurrentPageIndex'] = null;
    if (empty($additions['Fields'])) {
      $params['Fields'] = null;
    }

    return array('Request' => array_merge($params, $additions));
  }

}
