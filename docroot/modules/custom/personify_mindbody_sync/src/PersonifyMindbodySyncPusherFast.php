<?php

namespace Drupal\personify_mindbody_sync;

use Drupal\mindbody\MindbodyException;

/**
 * Class PersonifyMindbodySyncPusherFast.
 *
 * @package Drupal\personify_mindbody_sync
 */
class PersonifyMindbodySyncPusherFast extends PersonifyMindbodySyncPusherBase {

  /**
   * {@inheritdoc}
   */
  public function push() {
    $this->pushClientsBatch($this->debug);
    parent::pushOrders($this->debug);
  }

  /**
   * Push clients in a batch to MindBody.
   *
   * @param bool $debug
   *   Mode.
   *
   * @return $this
   *   Returns itself for chaining.
   */
  private function pushClientsBatch($debug = TRUE) {
    $data = $this->wrapper->getProxyData();

    foreach ($data as $id => $entity) {
      $user_id = $entity->field_pmc_user_id->value;
      $personifyData = unserialize($entity->field_pmc_personify_data->value);

      // Push only items which were not pushed before.
      if ($entity->get('field_pmc_mindbody_client_data')->isEmpty()) {
        $this->clientIds[$user_id] = parent::prepareClientObject($user_id, $personifyData, $debug);
      }
    }

    // Locate already synced clients.
    try {
      $result = $this->client->call(
        'ClientService',
        'GetClients',
        ['ClientIDs' => array_keys($this->clientIds)],
        FALSE
      );
    }
    catch (MindbodyException $e) {
      $msg = 'Failed to get clients list: %error';
      $this->logger->critical($msg, ['%error' => $e->getMessage()]);
      return $this;
    }

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
        unset($this->clientIds[$client->ID]);

        // Update cached entity with client's data if first time.
        $this->updateClientData($client->ID, $client);
      }
    }
    elseif ($result->GetClientsResult->ErrorCode != 200) {
      $msg = 'Error from MindBody: %error';
      $this->logger->critical($msg, ['%error' => serialize($result)]);
      return $this;
    }

    // Let's push new clients to MindBody.
    $push_clients = array_values($this->clientIds);
    if (!empty($push_clients)) {
      $clients_for_cache = [];
      try {
        $result = $this->client->call(
          'ClientService',
          'AddOrUpdateClients',
          ['Clients' => $push_clients],
          FALSE
        );
      }
      catch (MindbodyException $e) {
        $msg = 'Failed to push the clients: %error';
        $this->logger->critical($msg, ['%error' => $e->getMessage()]);
        return $this;
      }
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
        $msg = 'Failed to push the clients: %error';
        $this->logger->critical($msg, ['%error' => serialize($result)]);
        return $this;
      }
    }
    return $this;
  }

}
