<?php

namespace Drupal\personify_mindbody_sync;

use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Field\FieldItemList;
use Drupal\Core\Logger\LoggerChannel;
use Drupal\Core\Logger\LoggerChannelFactory;
use Drupal\mindbody_cache_proxy\MindbodyCacheProxyInterface;
use Drupal\personify_mindbody_sync\Entity\PersonifyMindbodyCache;

/**
 * Class PersonifyMindbodySyncPusher.
 *
 * @package Drupal\personify_mindbody_sync
 */
class PersonifyMindbodySyncPusher implements PersonifyMindbodySyncPusherInterface {

  const TEST_CLIENT_ID = '69696969';

  /**
   * Drupal\personify_mindbody_sync\PersonifyMindbodySyncWrapper definition.
   *
   * @var PersonifyMindbodySyncWrapper
   */
  protected $wrapper;

  /**
   * Logger channel.
   *
   * @var LoggerChannel
   */
  protected $logger;

  /**
   * Config factory.
   *
   * @var ConfigFactory
   */
  protected $config;

  /**
   * Array of Client IDs for processing to Mindbody.
   *
   * @var array
   */
  private $clientIds = [];

  /**
   * MindBody cache client.
   *
   * @var \Drupal\mindbody_cache_proxy\MindbodyCacheProxyInterface
   */
  private $client;

  /**
   * The list of services.
   *
   * @var array
   */
  private $services;

  /**
   * PersonifyMindbodySyncPusher constructor.
   *
   * @param \Drupal\personify_mindbody_sync\PersonifyMindbodySyncWrapper $wrapper
   *   Data wrapper.
   * @param \Drupal\mindbody_cache_proxy\MindbodyCacheProxyInterface $client
   *   MindBody caching client.
   * @param \Drupal\Core\Logger\LoggerChannelFactory $logger_factory
   *   Logger factory.
   */
  public function __construct(
    PersonifyMindbodySyncWrapper $wrapper,
    MindbodyCacheProxyInterface $client,
    ConfigFactory $config,
    LoggerChannelFactory $logger_factory
  ) {
    $this->wrapper = $wrapper;
    $this->logger = $logger_factory->get(PersonifyMindbodySyncWrapper::CHANNEL);
    $this->client = $client;
    $this->config = $config;
  }

  /**
   * {@inheritdoc}
   */
  public function push() {
    $this->pushClients();
//    $this->pushOrders();
  }

  /**
   * Process new and existing clients from Personify to MindBody.
   *
   * @return $this
   *   Returns itself for chaining.
   */
  private function pushClients() {
    $env = \Drupal::service('environment_config.handler')->getEnvironmentIndicator('mindbody.settings');
    $debug = TRUE;
    if ($env == 'production') {
      $debug = FALSE;
    }

    /** @var PersonifyMindbodyCache $entity */
    foreach ($this->wrapper->getProxyData() as $id => $entity) {
      $user_id = $entity->field_pmc_user_id->value;
      $personifyData = unserialize($entity->field_pmc_personify_data->value);

      // Push only items which were not pushed before.
      if ($entity->get('field_pmc_mindbody_client_data')->isEmpty()) {
        $this->clientIds[$user_id] = new \SoapVar(
          [
            'NewID' => $debug ? $user_id : self::TEST_CLIENT_ID,
            'ID' => $debug ? $user_id : self::TEST_CLIENT_ID,
            'FirstName' => !empty($personifyData->FirstName) ? $personifyData->FirstName : 'Non existent within Personify: FirstName',
            'LastName' => !empty($personifyData->LastName) ? $personifyData->LastName : 'Non existent within Personify: LastName',
            'Email' => !empty($personifyData->PrimaryEmail) ? $personifyData->PrimaryEmail : 'Non existent within Personify: Email',
            'BirthDate' => !empty($personifyData->BirthDate) ? $personifyData->BirthDate : '1970-01-01T00:00:00',
//              'MobilePhone' => !empty($personifyData->PrimaryPhone) ? $personifyData->PrimaryPhone : '0000000000',
            'MobilePhone' => '0000000000',
            // @todo recheck on prod. Required field get mad.
            'AddressLine1' => 'Non existent within Personify: AddressLine1',
            'City' => 'Non existent within Personify: City',
            'State' => 'NA',
            'PostalCode' => '00000',
            'ReferredBy' => 'Non existent within Personify: ReferredBy'
          ],
          SOAP_ENC_OBJECT,
          'Client',
          'http://clients.mindbodyonline.com/api/0_5'
        );
      }
    }

    // Locate already synced clients.
    $result = $this->client->call(
      'ClientService',
      'GetClients',
      ['ClientIDs' => array_keys($this->clientIds)],
      FALSE
    );

    if ($result->GetClientsResult->ErrorCode == 200 && $result->GetClientsResult->ResultCount != 0) {
      // Got it, there are clients, pushed already.
      $remote_clients = [];
      if ($result->GetClientsResult->ResultCount == 1) {
        $remote_clients[] = $result->GetClientsResult->Clients->Client;
      }
      else {
        $remote_clients = $result->GetClientsResult->Clients->Client;
      }

      // We've found a few clients already. Let's filter them out.
      foreach ($remote_clients as $client) {
        // Skip users already saved into cache.
        // @todo I'm guessing ID is not unique within MindBody.
        unset($this->clientIds[$client->ID]);

        // Updating local storage about MindBody client's data if first time.
        $this->updateClientData($client->ID, $client);
      }
    }
    elseif ($result->GetClientsResult->ErrorCode != 200) {
      // @todo consider throw Exception.
      $msg = '[DEV] Error from MindBody: %error';
      $this->logger->critical($msg, ['%error' => serialize($result)]);
      return $this;
    }

    // Let's push new clients to MindBody.
    $push_clients = array_values($this->clientIds);
    if (!empty($push_clients)) {
      $clients_for_cache = [];
      $result = $this->client->call(
        'ClientService',
        'AddOrUpdateClients',
        ['Clients' => $push_clients],
        FALSE
      );
      if ($result->AddOrUpdateClientsResult->ErrorCode == 200) {
        // Saving succeeded. Store cache data for later usage.
        if (count($push_clients) == 1) {
          $clients_for_cache[] = $result->AddOrUpdateClientsResult->Clients->Client;
        }
        else {
          $clients_for_cache = $result->AddOrUpdateClientsResult->Clients->Client;
        }
        foreach ($clients_for_cache as $client) {
          $this->clientIds[$client->ID] = $client;

          /* Note, the data will not be pushed if the client ID was
          overridden for the testing purposes. */
          $this->updateClientData($client->ID, $client);
        }
      }
      else {
        // @todo consider throw Exception.
        // @todo wite status message for all entities were not pushed.
        $msg = '[DEV] Failed to push the clients: %error';
        $this->logger->critical($msg, ['%error' => serialize($result)]);
        return $this;
      }
    }
    return $this;
  }

  /**
   * Update appropriate cache entities with client response data.
   *
   * @param $client_id string
   *   Client ID.
   * @param $data mixed
   *   Client data.
   */
  private function updateClientData($client_id, $data) {
    $cache_entities = $this->getEntityByClientId($client_id);
    if (!empty($cache_entities)) {
      foreach ($cache_entities as $cache_entity) {
        if ($cache_entity->get('field_pmc_mindbody_client_data')->isEmpty()) {
          $cache_entity->set('field_pmc_mindbody_client_data', serialize($data));
          $cache_entity->save();
        }
      }
    }
  }

  /**
   * Statically cached entity getter by ID.
   *
   * @param string $id
   *   ID been searched by.
   *
   * @return PersonifyMindbodyCache|bool
   *   List of entities or FALSE.
   */
  private function getEntityByClientId($id = '') {
    $entities = [];

    if ($id == NULL) {
      return FALSE;
    }

    $entity = &drupal_static(__FUNCTION__ . $id);
    if (isset($entity)) {
      return $entity;
    }

    $ids = \Drupal::entityQuery('personify_mindbody_cache')
      ->condition('field_pmc_user_id', $id)
      ->execute();

    if (!$ids) {
      return FALSE;
    }

    foreach ($ids as $id) {
      if (isset($this->wrapper->getProxyData()[$id])) {
        $entities[] = $this->wrapper->getProxyData()[$id];
      }
    }

    if (empty($entities)) {
      return FALSE;
    }

    return $entities;
  }

  private function pushOrders() {
    $config = \Drupal::service('environment_config.handler')->getActiveConfig('mindbody.settings');

    $env = \Drupal::service('environment_config.handler')->getEnvironmentIndicator('mindbody.settings');
    $debug = TRUE;
    if ($env == 'production') {
      $debug = FALSE;
    }

    $source = $this->wrapper->getSourceData();

    if (!$debug) {
      // Limit count of orders for Production.
      $source = array_slice($source, 0, 1);
    }

    $locations = $this->getAllLocationsFromOrders($source);
    foreach ($locations as $location => $count) {
      // Obtain Service ID.
      $params = [
        'LocationID' => $location,
        'HideRelatedPrograms' => TRUE,
      ];

      $response = $this->client->call(
        'SaleService',
        'GetServices',
        $params,
        FALSE
      );

      $this->services[$location] = $response->GetServicesResult->Services->Service;
    }

    // Loop through orders.
    $all_orders = [];

    foreach ($source as $id => $order) {
      // Do not push the order if it's already pushed.
      $cache_entity = $this->wrapper->findOrder($order->OrderNo, $order->OrderLineNo);
      if ($cache_entity) {
        $order_data = $cache_entity->get('field_pmc_mindbody_order_data');
        if (!$order_data->isEmpty()) {
          // Just skip this order.
          continue;
        }
      }

      $service = $this->getServiceByProductCode($order->ProductCode);
      if (!$service) {
        $this->logger->error('Failed to find a service with the code: %code', ['%code' => $order->ProductCode]);
        continue;
      }

      $all_orders[$order->MasterCustomerId][$order->OrderLineNo] = [
        'UserCredentials' => [
          // According to documentation we can use credentials, but with underscore at the beginning of username.
          // @see https://developers.mindbodyonline.com/Develop/Authentication.
          'Username' => '_' . $config['sourcename'],
          'Password' => $config['password'],
          'SiteIDs' => [
            $config['site_id'],
          ],
        ],
        'ClientID' => $debug ? $order->MasterCustomerId : self::TEST_CLIENT_ID,
        'CartItems' => [
          'CartItem' => [
            'Quantity' => $order->OrderQuantity,
            'Item' => new \SoapVar(
              [
                'ID' => $service->ID,
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
              // Custom payment ID?
              'ID' => 18,
            ],
            SOAP_ENC_ARRAY,
            'CustomPaymentInfo',
            'http://clients.mindbodyonline.com/api/0_5'
          ),
        ],
      ];

      // Push the order.
      $response = $this->client->call(
        'SaleService',
        'CheckoutShoppingCart',
        $all_orders[$order->MasterCustomerId][$order->OrderLineNo],
        FALSE
      );
      if ($response->CheckoutShoppingCartResult->ErrorCode == 200) {
        // @todo: make multivalue field for order response.
        $cache_entity = $this->wrapper->findOrder($order->OrderNo, $order->OrderLineNo);
        if ($cache_entity) {
          $cache_entity->set('field_pmc_mindbody_order_data', serialize($response->CheckoutShoppingCartResult->ShoppingCart));
          $cache_entity->save();
        }
      }
      else {
        // @todo consider throw Exception.
        $this->logger->critical(
          '[DEV] Error from MindBody: %error',
          ['%error' => serialize($response)]
        );
        return $this;
      }
    }
    return $this;

  }

  /**
   * Get service ID by Product Code.
   *
   * @param $code string
   *   Product code.
   *
   * @return mixed
   *   Service ID.
   */
  private function getServiceByProductCode($code) {
    $map = [
      'PT_NMP_1_SESS_30_MIN' => '10101',
      'PT_12_SESS_30_MIN' => '10110',
      'PT_NMP_12_SESS_30_MIN' => '10106',
      'PT_20_SESS_30_MIN' => '10111',
      'PT_NMP_20_SESS_30_MIN' => '10107',
      'PT_3_SESS_30_MIN' => '10108',
      'PT_NMP_3_SESS_30_MIN' => '10103',
      'PT_6_SESS_30_MIN' => '10109',
      'PT_NMP_6_SESS_30_MIN' => '10104',
      'PT_1_SESS_60_MIN' => '10112',
      'PT_NMP_1_SESS_60_MIN' => '10105',
      'PT_12_SESS_60_MIN' => '10119',
      'PT_NMP_12_SESS_60_MIN' => '10115',
      'PT_20_SESS_60_MIN' => '10120',
      'PT_NMP_20_SESS_60_MIN' => '10116',
      'PT_3_SESS_60_MIN' => '10117',
      'PT_NMP_3_SESS_60_MIN' => '10113',
      'PT_6_SESS_60_MIN' => '10118',
      'PT_NMP_6_SESS_60_MIN' => '10114',
      'PT_1_SESS_30_MIN' => '10101',
      'PT_BY_NMP_1_SESS_30_M' => '10131',
      'PT_BY_MP_1_SESS_30_MI' => '10172',
      'PT_BY_MP_12_SESS_30_M' => '10174',
      'PT_BY_NMP_12_SESS_30_' => '10138',
      'PT_BY_MP_6_SESS_30_MI' => '10173',
      'PT_BY_NMP_6_SESS_30_M' => '10137',
      'PT_BY_NMP_1_SESS_60_M' => '10127',
      'PT_BY_MP_12_SESS_60_M' => '10129',
      'PT_BY_NMP_12_SESS_60M' => '10176',
      'PT_BY_MP_20_SESS_60_M' => '10130',
      'PT_BY_NMP_20_SESS_60M' => '10177',
      'PT_BY_MP_6_SESS_60_MI' => '10136',
      'PT_BY_NMP_6_SESS_60_M' => '10175',
      'PT_BY_MP_1_SESS_60_MI' => '10126',
      'PT_BY_MP_INTRO' => '10134',
    ];

    preg_match("/\d+_(PT_.*)/", $code, $test);
    if (!$test[1]) {
      return FALSE;
    }

    // Service ID.
    if (!array_key_exists($test[1], $map)) {
      return FALSE;
    }
    $id = $map[$test[1]];

    // Location ID.
    $location_id = explode('_', $code)[0];

    foreach ($this->services as $location => $services) {
      if ($location == $location_id) {
        foreach ($services as $service) {
          if ($service->ID == $id) {
            return $service;
          }
        }
      }
    }

    return FALSE;
  }

  /**
   * Push orders from proxy to MindBody.
   */
  private function _pushOrders() {
    $env = \Drupal::service('environment_config.handler')->getEnvironmentIndicator('mindbody.settings');
    $config = \Drupal::service('environment_config.handler')->getActiveConfig('mindbody.settings');

    $source = $this->wrapper->getSourceData();

    if ($env == 'staging') {
      // We are working with fake data here.

      // Andover.
      $location_id = 1;

      // Obtain Service ID.
      $params = [
        'LocationID' => $location_id,
        'HideRelatedPrograms' => TRUE,
      ];

      $response = $this->client->call(
        'SaleService',
        'GetServices',
        $params,
        FALSE
      );
      $services = $response->GetServicesResult->Services->Service;

      foreach ($source as $order) {
        $rand = rand(0, count($services) - 1);
        $service_id = $services[$rand]->ID;

        $card_payment_info = new \SoapVar(
          [
            'CreditCardNumber' => '1234-4567-7458-4567',
            'Amount' => $services[$rand]->Price,
            'BillingAddress' => '123 Happy Ln',
            'BillingCity' => 'Santa Ynez',
            'BillingState' => 'CA',
            'BillingPostalCode' => '93455',
            'ExpYear' => '2017',
            'ExpMonth' => '7',
            'BillingName' => 'John Berky',
          ],
          SOAP_ENC_ARRAY,
          'CreditCardInfo',
          'http://clients.mindbodyonline.com/api/0_5'
        );

        // Let's place the order.
        $params = [
          // @todo Be careful about (int). MindBody stores string!!!
          'ClientID' => $order->MasterCustomerId,
          // Without Test "Card Authorization Failed
          // mb.Core.BLL.Transaction failed validation Could not determine
          // the type of credit card.".
          'Test' => TRUE,
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
            'PaymentInfo' => $card_payment_info
          ],
        ];

        $response = $this->client->call(
          'SaleService',
          'CheckoutShoppingCart',
          $params,
          FALSE
        );
        if ($response->CheckoutShoppingCartResult->Status == 'Success') {
          $this->logger->info(
            $env . ' : ShoppingCart succeeded ' . print_r(
              $response->CheckoutShoppingCartResult->ShoppingCart,
              TRUE
            )
          );
        }
        else {
          $this->logger->info(
            $env . ' : ShoppingCart failed with the result ' . print_r(
              $response->CheckoutShoppingCartResult,
              TRUE
            )
          );
        }
      }

    }
    else {
      // @todo Add production push logic.
      // Get all locations from sourceData.
      // Get all services per locations.
      $services = [];
      foreach ($this->getAllLocationsFromOrders($source) as $location => $count) {

        // Obtain Service ID.
        $params = [
          'LocationID' => $location,
          'HideRelatedPrograms' => TRUE,
        ];

        $response = $this->client->call(
          'SaleService',
          'GetServices',
          $params,
          FALSE
        );
        $services[$location] = $response->GetServicesResult->Services->Service;
      }
      // Loop through orders.
      $all_orders = [];
      foreach ($source as $id => $order) {
        $all_orders[$order->MasterCustomerId][$order->OrderLineNo] = [
          'UserCredentials' => [
            // According to documentation we can use credentials, but with underscore at the beginning of username.
            // @see https://developers.mindbodyonline.com/Develop/Authentication.
            'Username' => '_' . $config['sourcename'],
            'Password' => $config['password'],
            'SiteIDs' => [
              $config['site_id'],
            ],
          ],
          'ClientID' => self::TEST_CLIENT_ID,
          'CartItems' => [
            'CartItem' => [
              'Quantity' => $order->OrderQuantity,
              'Item' => new \SoapVar(
                [
                  'ID' => 10101,
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
                'Amount' => 55,
                // Custom payment ID?
                'ID' => 18,
              ],
              SOAP_ENC_ARRAY,
              'CustomPaymentInfo',
              'http://clients.mindbodyonline.com/api/0_5'
            ),
          ],
        ];
        // Push all orders.
        $response = $this->client->call('SaleService', 'CheckoutShoppingCart', $all_orders[$order->MasterCustomerId][$order->OrderLineNo], FALSE);
        $a = 10;
      }
      $this->logger->error(
        $env . ' : Not implemented for this environment yet.'
      );
    }

  }

//  private function getServiceByProductCode($code) {
//    $map = [
//      'PT Express 30 min - 1 (NON MEMBER)' => '10101',
//    ];
//
//    $id = $map[$code];
//
//  }

  /**
   * Get Location ID from Order object.
   *
   * @param \stdClass $order
   *   Order to be processed.
   *
   * @return string
   *   String of LocationID.
   */
  private function getLocationForOrder(\stdClass $order) {
    $data = explode('_', $order->ProductCode);
    return $data[0];
  }

  /**
   * Pre populate locations.
   *
   * @param array $orders
   *   Assoc array with ID as keys and count of orders as value.
   *
   * @return array
   *   Locations.
   */
  private function getAllLocationsFromOrders(array $orders) {
    $locations = [];
    foreach ($orders as $id => $order) {
      $loc_id = $this->getLocationForOrder($order);
      if (!isset($locations[$loc_id])) {
        $locations[$loc_id] = 0;
      }
      else {
        $locations[$loc_id]++;
      }
    }
    return $locations;
  }

}
