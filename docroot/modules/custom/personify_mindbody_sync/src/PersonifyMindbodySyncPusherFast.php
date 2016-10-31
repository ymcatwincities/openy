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
    if (!$push = $this->getNotPushedOrders()) {
      $this->logger->debug('All orders have been already pushed. Exit.');
      return;
    }

    $this->pushClientsSingle();
    $this->pushOrders($push);
  }

  /**
   * Push clients in a batch to MindBody.
   *
   * @return $this
   *   Returns itself for chaining.
   */
  private function pushClientsBatch() {
    $this->logger->debug('The Push clients to MindBody has been started.');
    if (!parent::filterOutClients()) {
      return $this;
    }

    // In test mode proceed orders only for test user.
    if (!$this->isProduction) {
      $clients = [];
      foreach ($this->clientIds as $client_id => $client_data) {
        if ($client_id != self::TEST_CLIENT_ID) {
          continue;
        }
        $clients[$client_id] = $client_data;
      }
      $this->clientIds = $clients;
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
        $this->updateStatusByClients(array_keys($this->clientIds), $e->getMessage());

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

          // Reset status.
          $this->updateStatusByClients([$client->ID], '');
        }
      }
      else {
        // A bunch of clients may failed with different reasons.
        // The only way is to debug the problem is to look in the system log.
        $this->updateStatusByClients(array_values($this->clientIds), 'Bulk user update fail. Look in the system log.');

        $msg = 'Failed to push the clients: %error';
        $this->logger->critical($msg, ['%error' => serialize($result)]);
      }
    }

    $msg = 'The Push clients to MindBody has been finished. %num clients have been pushed.';
    $this->logger->debug($msg, ['%num' => count($push_clients)]);
  }

}
