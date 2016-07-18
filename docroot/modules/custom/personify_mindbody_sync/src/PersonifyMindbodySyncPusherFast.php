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
    if (!parent::filerOutClients()) {
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
