<?php

namespace Drupal\personify_mindbody_sync;

use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Logger\LoggerChannel;
use Drupal\Core\Logger\LoggerChannelFactory;
use Drupal\mindbody\MindbodyException;
use Drupal\mindbody_cache_proxy\MindbodyCacheProxyInterface;
use Drupal\personify_mindbody_sync\Entity\PersonifyMindbodyCache;

/**
 * Class PersonifyMindbodySyncPusherBase.
 *
 * @package Drupal\personify_mindbody_sync
 */
abstract class PersonifyMindbodySyncPusherBase implements PersonifyMindbodySyncPusherInterface {

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
  protected $clientIds = [];

  /**
   * MindBody cache client.
   *
   * @var \Drupal\mindbody_cache_proxy\MindbodyCacheProxyInterface
   */
  protected $client;

  /**
   * The list of services.
   *
   * @var array
   */
  protected $services;

  /**
   * Mode.
   *
   * @var bool
   */
  protected $debug = TRUE;

  /**
   * PersonifyMindbodySyncPusher constructor.
   *
   * @param \Drupal\personify_mindbody_sync\PersonifyMindbodySyncWrapper $wrapper
   *   Data wrapper.
   * @param \Drupal\mindbody_cache_proxy\MindbodyCacheProxyInterface $client
   *   MindBody caching client.
   * @param ConfigFactory $config
   *   Config factory.
   * @param \Drupal\Core\Logger\LoggerChannelFactory $logger_factory
   *   Logger factory.
   */
  public function __construct(PersonifyMindbodySyncWrapper $wrapper, MindbodyCacheProxyInterface $client, ConfigFactory $config, LoggerChannelFactory $logger_factory) {
    $this->wrapper = $wrapper;
    $this->logger = $logger_factory->get(PersonifyMindbodySyncWrapper::CHANNEL);
    $this->client = $client;
    $this->config = $config;

    // Check the mode.
    $settings = $this->config->get('personify_mindbody_sync.settings');
    $this->debug = $settings->get('debug');
  }

  /**
   * Push orders.
   *
   * @param bool $debug
   *   Mode.
   *
   * @return $this
   *   Returns itself for chaining.
   */
  protected function pushOrders($debug = TRUE) {
    $config = \Drupal::service('environment_config.handler')->getActiveConfig('mindbody.settings');
    $source = $this->wrapper->getSourceData();

    if ($debug) {
      // Limit count of orders for while debugging.
      $source = array_slice($source, 0, 1);
    }

    $locations = $this->getAllLocationsFromOrders($source);
    foreach ($locations as $location => $count) {
      // Obtain Service ID.
      $params = [
        'LocationID' => $location,
        'HideRelatedPrograms' => TRUE,
      ];

      try {
        $response = $this->client->call(
          'SaleService',
          'GetServices',
          $params,
          FALSE
        );
      }
      catch (MindbodyException $e) {
        $msg = 'Failed to get services form Mindbody: %error';
        $this->logger->critical($msg, ['%error' => $e->getMessage()]);
        return $this;
      }

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
        $msg = 'Failed to find a service with the code: %code';
        $this->logger->error($msg, ['%code' => $order->ProductCode]);
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
        'ClientID' => $debug ? self::TEST_CLIENT_ID : $order->MasterCustomerId,
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
      try {
        $response = $this->client->call(
          'SaleService',
          'CheckoutShoppingCart',
          $all_orders[$order->MasterCustomerId][$order->OrderLineNo],
          FALSE
        );
      }
      catch (MindbodyException $e) {
        $msg = 'Failed to push order to the MindBody: %msg';
        $this->logger->error($msg, ['%msg' => $e->getMessage()]);
        // Skip this order. Continue with next.
        continue;
      }
      if ($response->CheckoutShoppingCartResult->ErrorCode == 200) {
        if ($cache_entity) {
          $cache_entity->set('field_pmc_mindbody_order_data', serialize($response->CheckoutShoppingCartResult->ShoppingCart));
          $cache_entity->save();
        }
      }
      else {
        // Write error to status message.
        if ($cache_entity) {
          $cache_entity->set('field_pmc_status_message', serialize($response));
          $cache_entity->save();
        }

        // Log an error.
        $msg = '[DEV] Failed to push order to MindBody: %error';
        $this->logger->critical($msg, ['%error' => serialize($response)]);
        return $this;
      }
    }
    return $this;

  }

  /**
   * Update appropriate cache entities with client response data.
   *
   * @param string $client_id
   *   Client ID.
   * @param mixed $data
   *   Client data.
   */
  protected function updateClientData($client_id, $data) {
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
  protected function getEntityByClientId($id = '') {
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

  /**
   * Get service ID by Product Code.
   *
   * @param string $code
   *   Product code.
   *
   * @return mixed
   *   Service ID.
   */
  protected function getServiceByProductCode($code) {
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
   * Get Location ID from Order object.
   *
   * @param \stdClass $order
   *   Order to be processed.
   *
   * @return string
   *   String of LocationID.
   */
  protected function getLocationForOrder(\stdClass $order) {
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
  protected function getAllLocationsFromOrders(array $orders) {
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

  /**
   * Prepare SoapVar object from Personify Data.
   *
   * @param int $user_id
   *   User ID.
   * @param \stdClass $data
   *   Personify data.
   * @param bool $debug
   *   Mode.
   *
   * @return \SoapVar
   *   Object ready to push to MindBody.
   */
  protected function prepareClientObject($user_id, \stdClass $data, $debug = TRUE) {
    $default_phone = '0000000000';

    // Fix AddressLine.
    $address = 'NA';

    // Try automatically fix phone.
    if (!$phone = $data->PrimaryPhone) {
      $phone = $default_phone;
    }
    else {
      // The phone should be like: 612-865-9139.
      $result = preg_grep("/^\d{3}-\d{3}-\d{4}$/", [$phone]);
      if (empty($result)) {
        // Phone is invalid. Append it to AddressLine.
        $address .= ' PrimaryPhone: ' . $phone;
        $phone = $default_phone;
      }
    }

    return new \SoapVar(
      [
        'NewID' => $debug ? self::TEST_CLIENT_ID : $user_id,
        'ID' => $debug ? self::TEST_CLIENT_ID : $user_id,
        'FirstName' => !empty($data->FirstName) ? $data->FirstName : 'Non existent within Personify: FirstName',
        'LastName' => !empty($data->LastName) ? $data->LastName : 'Non existent within Personify: LastName',
        'Email' => !empty($data->PrimaryEmail) ? $data->PrimaryEmail : 'Non existent within Personify: Email',
        'BirthDate' => !empty($data->BirthDate) ? $data->BirthDate : '1970-01-01T00:00:00',
        'MobilePhone' => $phone,
        'AddressLine1' => $address,
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
