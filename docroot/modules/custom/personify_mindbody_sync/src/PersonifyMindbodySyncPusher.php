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
        $this->clientIds[$entity->get('field_user_id')->getValue()[0]['value']] = [
          'NewID' => $entity->get('field_user_id')->getValue()[0]['value'],
          'FirstName' => $personifyData->FirstName,
          'LastName' => $personifyData->LastName,
          'Email' => $personifyData->PrimaryEmail,
          'AddressLine1' => '',
          'City' => '',
          'PostalCode' => '',
          'ReferredBy' => '',
          'BirthDate' => $personifyData->BirthDate,
          'State' => 'Nevada',
          'MobilePhone' => $personifyData->PrimaryPhone,
        ];
      }
    }
    $result = $this->client->call('ClientService', 'GetClients', ['ClientIDs' => array_keys($this->clientIds)], FALSE);
    $count = $result->GetClientsResult->ResultCount;
    if ($count != 0) {
      // @todo We've found a few clients already. Let's filter them out.
      $this->logger->error('We\'ve found a few clients aleady. Let\'s filter them out');
    }
    else {
      // @todo Save new clients to MindBody.
      $result = $this->client->call('ClientService', 'AddOrUpdateClients', ['ClientIDs' => array_values($this->clientIds)], FALSE);
      return $this->clientIds;
    }

  }

}
