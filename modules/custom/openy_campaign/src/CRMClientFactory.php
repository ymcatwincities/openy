<?php

namespace Drupal\openy_campaign;

use Symfony\Component\Console\Exception\LogicException;

/**
 * Factory to get the class to connect to CRM system i.e. Personify.
 */
class CRMClientFactory {

  /**
   * Get CRM client from the configuration.
   */
  public function getClient() {
    $clientKey = \Drupal::config('openy_campaign.settings')->get('client');

    $client = \Drupal::getContainer()->get($clientKey);

    if (!in_array('Drupal\openy_campaign\CRMClientInterface', class_implements(get_class($client)))) {
      throw new LogicException('CRM Client doe not implement CRMClientInterface.');
    }

    return $client;

  }

}
