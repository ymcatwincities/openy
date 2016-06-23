<?php

namespace Drupal\personify_mindbody_sync;

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
   * Array of Client IDs for processing to Mindbody.
   *
   * @var array
   */
  private $clientIds;

  /**
   * MindBody cache client.
   *
   * @var \Drupal\mindbody_cache_proxy\MindbodyCacheProxyInterface
   */
  private $client;

  /**
   * PersonifyMindbodySyncPusher constructor.
   *
   * @param PersonifyMindbodySyncWrapper $wrapper
   *   Data wrapper.
   * @param LoggerChannelFactory $logger_factory
   *   Logger factory.
   */
  public function __construct(PersonifyMindbodySyncWrapper $wrapper, MindbodyCacheProxyInterface $client, LoggerChannelFactory $logger_factory) {
    $this->wrapper = $wrapper;
    $this->logger = $logger_factory->get(PersonifyMindbodySyncWrapper::CHANNEL);
    $this->client = $client;
  }

  /**
   * {@inheritdoc}
   */
  public function push() {
    // Have a look at YmcaMindbodyExamples.php for the example.
    $a = 10;
    $this->getClientIds();
    foreach ($this->wrapper->getProxyData() as $id => $entity) {

    }
  }

  private function getClientIds() {
    $this->clientIds = [];
    /**
     * @var integer $id
     * @var PersonifyMindbodyCache $entity
     */
    foreach ($this->wrapper->getProxyData() as $id => $entity) {
      $personifyData = unserialize($entity->get('field_pmc_data')->getValue()[0]['value']);
      if ($entity->get('field_pmc_mindbody_data')->isEmpty()) {
        $this->clientIds[$entity->get('field_pmc_user_id')->getValue()[0]['value']] = new \SoapVar([
          'NewID' => $entity->get('field_pmc_user_id')->getValue()[0]['value'],
          'FirstName' => $personifyData->FirstName,
          'LastName' => $personifyData->LastName,
          'Email' => $personifyData->PrimaryEmail,
          //'AddressLine1' => '',
          //'City' => '',
          //'PostalCode' => '',
          //'ReferredBy' => '',
          'BirthDate' => $personifyData->BirthDate,
          //'State' => '',
          'MobilePhone' => $personifyData->PrimaryPhone,
        ],
          SOAP_ENC_OBJECT,
          'Client',
          'http://clients.mindbodyonline.com/api/0_5');
      }
    }

    $result = $this->client->call('ClientService', 'GetClients', ['ClientIDs' => [array_shift(array_keys($this->clientIds))]], FALSE);

    if ($result->GetClientsResult->ErrorCode == 200 && $result->GetClientsResult->ResultCount != 0) {
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
        // @todo I'm guessing ID is not unique.
        unset($this->clientIds[$client->ID]);
        $cache_id = \Drupal::entityQuery('personify_mindbody_cache')
          ->condition('field_pmc_user_id', $client->ID)
          ->execute();
        /** @var PersonifyMindbodyCache $cache_entity */
        $cache_entity = $this->wrapper->getProxyData()[array_shift($cache_id)];
        // Updating local storage about MindBody client's data if first time.
        if ($cache_entity->get('field_pmc_mindbody_data')->isEmpty()) {
          // @todo make it more smart via diff with old data for getting actual.
          $cache_entity->set('field_pmc_mindbody_data', serialize($client));
          $cache_entity->save();
        }
      }
    }
    elseif ($result->GetClientsResult->ErrorCode != 200) {
      $this->logger->critical('[DEV] Error from MindBody: %error', ['%error' => print_r($result, TRUE)]);
      return;
    }
    // @todo Save new clients to MindBody.
    $test = array_values($this->clientIds);
    $result = $this->client->call('ClientService', 'AddOrUpdateClients', ['Clients' => $test], FALSE);
    return $this->clientIds;
  }

}
