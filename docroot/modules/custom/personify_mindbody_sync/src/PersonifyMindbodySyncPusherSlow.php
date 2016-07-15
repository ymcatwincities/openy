<?php

namespace Drupal\personify_mindbody_sync;

use Drupal\mindbody\MindbodyException;

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
    if (!parent::filerOutClients()) {
      return;
    }

    // Let's push new clients to MindBody.
    foreach ($this->clientIds as $client_id => $client) {
      try {
        $result = $this->client->call(
          'ClientService',
          'AddOrUpdateClients',
          ['Clients' => [$client]],
          FALSE
        );
      }
      catch (MindbodyException $e) {
        $msg = 'Failed to push (exception) single client: %error';
        $this->logger->critical($msg, ['%error' => $e->getMessage()]);
        // Continue with the next client.
        continue;
      }
      if ($result->AddOrUpdateClientsResult->ErrorCode == 200) {
        $response = $result->AddOrUpdateClientsResult->Clients->Client;
        $this->updateClientData($client_id, $response);
      }
      else {
        // Something went wrong.
        $msg = 'Failed to push single client: %error';
        $this->logger->critical($msg, ['%error' => serialize($result)]);
      }
    }
  }

}
