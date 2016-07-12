<?php

namespace Drupal\personify_mindbody_sync;

use Drupal\Core\Config\ConfigFactory;
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
    // Have a look at YmcaMindbodyExamples.php for the example.
    $this->pushClients();
    $this->pushOrders();
  }

  /**
   * Process new and existing clients from Personify to MindBody.
   *
   * @return $this
   *   Returns itself for chaining.
   */
  private function pushClients() {
    $env = \Drupal::service('environment_config.handler')->getEnvironmentIndicator('mindbody.settings');
    if ($env == 'staging') {

      /** @var PersonifyMindbodyCache $entity */
      foreach ($this->wrapper->getProxyData() as $id => $entity) {
        $user_id = $entity->field_pmc_user_id->value;
        $personifyData = unserialize($entity->field_pmc_data->value);

        // Push only items which were not pushed before.
        if ($entity->get('field_pmc_mindbody_data')->isEmpty()) {
          $this->clientIds[$user_id] = new \SoapVar(
            [
              'NewID' => $user_id,
              'ID' => $user_id,
              'FirstName' => !empty($personifyData->FirstName) ? $personifyData->FirstName : 'Non existent within Personify: FirstName',
              'LastName' => !empty($personifyData->LastName) ? $personifyData->LastName : 'Non existent within Personify: LastName',
              'Email' => !empty($personifyData->PrimaryEmail) ? $personifyData->PrimaryEmail : 'Non existent within Personify: Email',
              'BirthDate' => !empty($personifyData->BirthDate) ? $personifyData->BirthDate : '1970-01-01T00:00:00',
              'MobilePhone' => !empty($personifyData->PrimaryPhone) ? $personifyData->PrimaryPhone : '0000000000',
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
          /** @var PersonifyMindbodyCache $cache_entity */
          if ($cache_entity = $this->getEntityByClientId($client->ID)) {
            // Updating local storage about MindBody client's data if first time.
            if ($cache_entity->get('field_pmc_mindbody_data')->isEmpty()) {
              // @todo make it more smart via diff with old data for getting actual.
              $cache_entity->set('field_pmc_mindbody_data', serialize($client));
              $cache_entity->save();
            }
          }
        }
      }
      elseif ($result->GetClientsResult->ErrorCode != 200) {
        // @todo consider throw Exception.
        $this->logger->critical(
          '[DEV] Error from MindBody: %error',
          ['%error' => serialize($result)]
        );
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
            /** @var PersonifyMindbodyCache $cache_entity */
            $cache_entity = $this->getEntityByClientId($client->ID);
            if ($cache_entity) {
              $cache_entity->set('field_pmc_mindbody_data', serialize($client));
              $cache_entity->save();
            }
          }
        }
        else {
          // @todo consider throw Exception.
          $this->logger->critical(
            '[DEV] Error from MindBody: %error',
            ['%error' => serialize($result)]
          );
          return $this;
        }
      }
      return $this;
    }
    else {
      // @todo Add production push logic.
      $this->logger->error('%env: not implemented for this environment yet.', ['%env' => $env]);
    }
  }

  /**
   * Statically cached entity getter by ID.
   *
   * @param string $id
   *   ID been searched by.
   *
   * @return PersonifyMindbodyCache|bool
   *   Entity of FALSE if not found.
   */
  private function getEntityByClientId($id = '') {
    if ($id == NULL) {
      return FALSE;
    }
    $entity = &drupal_static(__FUNCTION__ . $id);
    if (isset($entity)) {
      return $entity;
    }

    $cache_id = \Drupal::entityQuery('personify_mindbody_cache')
      ->condition('field_pmc_user_id', $id)
      ->execute();
    $cache_id = array_shift($cache_id);
    if ($cache_id == NULL) {
      return FALSE;
    }
    if (!isset($this->wrapper->getProxyData()[$cache_id])) {
      return FALSE;
    }
    /** @var PersonifyMindbodyCache $entity */
    $entity = $this->wrapper->getProxyData()[$cache_id];

    return $entity;
  }

  /**
   * Push orders from proxy to MindBody.
   */
  private function pushOrders() {

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
      foreach ($this->getAllLocationsFromOrders($source) as $location) {

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
      // Loop through events
      $all_orders = [];
      foreach ($source as $id => $order) {
        $all_orders[$order->MasterCustomerId] = [
          'UserCredentials' => [
            // According to documentation we can use credentials, but with underscore at the beginning of username.
            // @see https://developers.mindbodyonline.com/Develop/Authentication.
            'Username' => '_' . $settings->get('sourcename'),
            'Password' => $settings->get('password'),
            'SiteIDs' => [
              $settings->get('site_id'),
            ],
          ],
        ];
        // $services[$this->getLocationForOrder($order)]->ID
      }
      $this->logger->error(
        $env . ' : Not implemented for this environment yet.'
      );
    }

  }

  /**
   * Get Location ID from Order object.
   *
   * @param \stdClass $order
   *   Order to be processed.
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
   */
  private function getAllLocationsFromOrders(array $orders) {
    $locations = [];
    foreach ($orders as $id => $order) {
      $locations[$this->getLocationForOrder($order)]++;
    }
    return $locations;
  }

}
