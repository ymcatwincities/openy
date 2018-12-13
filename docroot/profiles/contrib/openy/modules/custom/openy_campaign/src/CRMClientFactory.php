<?php

namespace Drupal\openy_campaign;

use Drupal\Core\Config\ConfigFactoryInterface;
use Symfony\Component\Console\Exception\LogicException;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Factory to get the class to connect to CRM system i.e. Personify.
 */
class CRMClientFactory {

  /**
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  private $configFactory;

  /**
   * @var \Symfony\Component\DependencyInjection\ContainerInterface
   */
  private $container;

  public function __construct(
    ConfigFactoryInterface $configFactory,
    ContainerInterface $container
  ) {
    $this->configFactory = $configFactory;
    $this->container = $container;
  }

  /**
   * Get CRM client from the configuration.
   */
  public function getClient() {
    $clientKey = $this->configFactory->get('openy_campaign.settings')->get('client');

    $client = $this->container->get($clientKey);

    if (!in_array('Drupal\openy_campaign\CRMClientInterface', class_implements(get_class($client)))) {
      throw new LogicException('CRM Client does not implement CRMClientInterface.');
    }

    return $client;

  }

}
