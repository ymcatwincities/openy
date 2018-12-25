<?php
// @codingStandardsIgnoreFile
namespace Drupal\ymca_mindbody;

use Drupal\mindbody_cache_proxy\MindbodyCacheProxy;
use Masterminds\HTML5\Exception;

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

  /**
   * Creates test order.
   */
  public function createTestOrder() {
    // In order to save free MindBody calls let's hardcode some variables.
    // Make sure you've created the client using createTestClient() method.

    $response = $this->proxy->call('SaleService', 'GetCustomPaymentMethods', []);

    $settings = \Drupal::config('mindbody.settings');

    $params = [
      'SaleID' => 12368,
    ];

    $response = $this->proxy->call('SaleService', 'GetSales', $params);

    $client_id = 69696969;
    // Ensure that the client exists...
    $params = [
      'ClientIDs' => [
        $client_id
      ],
    ];
    $response = $this->proxy->call('ClientService', 'GetClients', $params);

    if ($response->GetClientsResult->Status != 'Success') {
      throw new Exception('Failed to get client');
    }

    // Andover.
    $location_id = 1;
    // Promo PT Express.
    $session_type_id = 55;

    // Obtain Service ID.
    $params = [
      'LocationID' => $location_id,
      'HideRelatedPrograms' => TRUE,
    ];

    $response = $this->proxy->call('SaleService', 'GetServices', $params);
    $services = $response->GetServicesResult->Services->Service;

    // Let's keep the first service.
    $service = reset($services);
    $service_id = $service->ID;

//    $card_payment_info = new \SoapVar(
//      [
//        'CreditCardNumber' => '1234-4567-7458-4567',
//        'Amount' => $service->Price,
//        'BillingAddress' => '123 Happy Ln',
//        'BillingCity' => 'Santa Ynez',
//        'BillingState' => 'CA',
//        'BillingPostalCode' => '93455',
//        'ExpYear' => '2017',
//        'ExpMonth' => '7',
//        'BillingName' => 'John Berky',
//      ],
//      SOAP_ENC_ARRAY,
//      'CreditCardInfo',
//      'http://clients.mindbodyonline.com/api/0_5'
//    );

    // Let's place the order.
    $params = [
      'UserCredentials' => [
        // According to documentation we can use credentials, but with underscore at the beginning of username.
        // @see https://developers.mindbodyonline.com/Develop/Authentication.
        'Username' => '_' . $settings->get('sourcename'),
        'Password' => $settings->get('password'),
        'SiteIDs' => [
          $settings->get('site_id'),
        ],
      ],
      // @todo Be carefull about (int). Mindbody stores string!!!
      'ClientID' => (int) $client_id,
      'CartItems' => [
        'CartItem' => [
          'Quantity' => 1,
          'Item' => new \SoapVar(
            [
              'ID' => $service_id
            ],
            SOAP_ENC_ARRAY,
            'Service',
            'http://clients.mindbodyonline.com/api/0_5'
          ),
          'DiscountAmount' => 0,
        ],
      ],
      'Payments' => [
        'PaymentInfo' => new \SoapVar(
          [
            'Amount' => $service->Price,
            'ID' => 18,
          ],
          SOAP_ENC_ARRAY,
          'CustomPaymentInfo',
          'http://clients.mindbodyonline.com/api/0_5'
        ),
      ],
    ];

    $response = $this->proxy->call('SaleService', 'CheckoutShoppingCart', $params, FALSE);
    return $response;
  }

}
