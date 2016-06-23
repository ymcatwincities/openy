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
    $this->getClientIds();
    foreach ($this->wrapper->getProxyData() as $id => $entity) {
      // @todo push orders.
    }
  }

  /**
   * Process new and existing clients from Personify to MindBody.
   *
   * @return $this
   *   Returns itself for chaining.
   */
  private function getClientIds() {
    /** @var PersonifyMindbodyCache $entity */
    foreach ($this->wrapper->getProxyData() as $id => $entity) {
      $personifyData = unserialize($entity->get('field_pmc_data')->getValue()[0]['value']);
      if ($entity->get('field_pmc_mindbody_data')->isEmpty()) {
        $this->clientIds[$entity->get('field_pmc_user_id')->getValue()[0]['value']] = new \SoapVar([
          'NewID' => $entity->get('field_pmc_user_id')->getValue()[0]['value'],
          'ID' => $entity->get('field_pmc_user_id')->getValue()[0]['value'],
          'FirstName' => $personifyData->FirstName,
          'LastName' => $personifyData->LastName,
          'Email' => $personifyData->PrimaryEmail,
          'BirthDate' => $personifyData->BirthDate,
          'MobilePhone' => $personifyData->PrimaryPhone,
        ],
          SOAP_ENC_OBJECT,
          'Client',
          'http://clients.mindbodyonline.com/api/0_5');
      }
    }

    // Locate already synced clients.
    $result = $this->client->call('ClientService', 'GetClients', ['ClientIDs' => array_keys($this->clientIds)], FALSE);

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
        $cache_entity = $this->getEntityByClientId($client->ID);
        // Updating local storage about MindBody client's data if first time.
        if ($cache_entity && $cache_entity->get('field_pmc_mindbody_data')->isEmpty()) {
          // @todo make it more smart via diff with old data for getting actual.
          $cache_entity->set('field_pmc_mindbody_data', serialize($client));
          $cache_entity->save();
        }
      }
    }
    elseif ($result->GetClientsResult->ErrorCode != 200) {
      // @todo consider throw Exception.
      $this->logger->critical('[DEV] Error from MindBody: %error', ['%error' => print_r($result, TRUE)]);
      return $this;
    }

    // Let's push new clients to MindBody.
    $push_clients = array_values($this->clientIds);
    if (!empty($push_clients)) {
      $clients_for_cache = [];
      $result = $this->client->call('ClientService', 'AddOrUpdateClients', ['Clients' => $push_clients], FALSE);
      if ($result->AddOrUpdateClientsResult->ErrorCode == 200) {
        // Saving succeeded. Store cache data for later usage.
        if (count($push_clients) == 1) {
          $clients_for_cache[] = $result->AddOrUpdateClientsResult->Clients->Client;
        }
        else {
          $clients_for_cache = $result->AddOrUpdateClientsResult->Clients->Client;
        }
        foreach ($clients_for_cache as $client) {
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
        $this->logger->critical('[DEV] Error from MindBody: %error', ['%error' => print_r($result, TRUE)]);
        return $this;
      }
    }
    return $this;
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

}
