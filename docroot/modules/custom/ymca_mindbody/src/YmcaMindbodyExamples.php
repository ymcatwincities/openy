<?php

namespace Drupal\ymca_mindbody;
use Drupal\mindbody_cache_proxy\MindbodyCacheProxy;

/**
 * Mindbody Examples.
 *
 * @package Drupal\mindbody
 */
class YmcaMindbodyExamples {

  /**
   * Proxy.
   *
   * @var MindbodyCacheProxy
   */
  protected $proxy;

  /**
   * YmcaMindbodyExamples constructor.
   *
   * @param MindbodyCacheProxy $proxy
   *   Cache proxy.
   */
  public function __construct(MindbodyCacheProxy $proxy) {
    $this->proxy = $proxy;
  }

  /**
   * Create test client.
   */
  public function createTestClient() {
    $params = [
      'Clients' => [
        'Client' => [
          'NewID' => '69696969',
          'FirstName' => 'ffw_test_ First Name',
          'LastName' => 'ffw_test Last Name',
          'Email' => 'ffw_test_email@example.com',
          'AddressLine1' => 'ffw_test AddressLine 1',
          'City' => 'ffw_test City',
          'PostalCode' => '37600',
          'ReferredBy' => 'ReferredBy',
          'BirthDate' => '2009-03-13T22:16:00',
          'State' => 'Nevada',
          'MobilePhone' => '69696969',
        ],
      ],
    ];

    $response = $this->proxy->call('ClientService', 'AddOrUpdateClients', $params, FALSE);
    return $response;
  }

  /**
   * Update test client.
   *
   * In order to update a client you should pass 'ID' and other required fields.
   * I believe, the best option is to pass all user data including changed
   * field.
   */
  public function updateTestClient() {
    $params = [
      'Clients' => [
        'Client' => [
          'ID' => '69696969',
          'FirstName' => 'ffw_test First Name (updated)',
          'LastName' => 'ffw_test Last Name (updated)',
          'Email' => 'ffw_test_email@example.com',
          'AddressLine1' => 'ffw_test AddressLine 1',
          'City' => 'ffw_test City',
          'PostalCode' => '37600',
          'ReferredBy' => 'ReferredBy',
          'BirthDate' => '2009-03-13T22:16:00',
          'State' => 'Nevada',
          'MobilePhone' => '69696969',
        ],
      ],
    ];

    $response = $this->proxy->call('ClientService', 'AddOrUpdateClients', $params, FALSE);
    return $response;
  }

}
