<?php

namespace Drupal\personify_mindbody_sync;

/**
 * Class PersonifyMindbodySyncPusherSlow.
 *
 * @package Drupal\personify_mindbody_sync
 */
class PersonifyMindbodySyncPusherSlow extends PersonifyMindbodySyncPusherBase {

  /**
   * {@inheritdoc}
   */
  public function push() {
    $this->pushClientsSingle($this->debug);
    parent::pushOrders($this->debug);
  }

  /**
   * Push clients to MindBody one by one.
   *
   * @param bool $debug
   *   Mode.
   */
  private function pushClientsSingle($debug = TRUE) {
    $data = $this->wrapper->getProxyData();

    foreach ($data as $id => $entity) {
      $user_id = $entity->field_pmc_user_id->value;
      $personifyData = unserialize($entity->field_pmc_personify_data->value);

      // Push only items which were not pushed before.
      if ($entity->get('field_pmc_mindbody_client_data')->isEmpty()) {
        $this->clientIds[$user_id] = parent::prepareClientObject(
          $user_id,
          $personifyData,
          $debug
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
        unset($this->clientIds[$client->ID]);

        // Update cached entity with client's data if first time.
        $this->updateClientData($client->ID, $client);
      }
    }
    elseif ($result->GetClientsResult->ErrorCode != 200) {
      $msg = '[DEV] Error from MindBody: %error';
      $this->logger->critical($msg, ['%error' => serialize($result)]);
    }

    // Let's push new clients to MindBody.
    foreach ($this->clientIds as $client_id => $client) {
      $result = $this->client->call(
        'ClientService',
        'AddOrUpdateClients',
        ['Clients' => [$client]],
        FALSE
      );
      if ($result->AddOrUpdateClientsResult->ErrorCode == 200) {
        $response = $result->AddOrUpdateClientsResult->Clients->Client;
        $this->updateClientData($client_id, $response);
      }
      else {
        // Something went wrong.
        $msg = '[DEV] Failed to push single client: %error';
        $this->logger->critical($msg, ['%error' => serialize($result)]);
      }
    }
  }

}
