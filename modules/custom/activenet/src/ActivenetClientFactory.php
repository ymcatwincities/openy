<?php

namespace Drupal\activenet;

use Drupal\Core\Config\ConfigFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class ActivenetClientFactory.
 *
 * @package Drupal\activenet
 */
class ActivenetClientFactory implements ActivenetClientFactoryInterface {


  /**
   * Config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs a new Activenet Client Factory instance.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   Config Factory.
   */
  public function __construct(ConfigFactoryInterface $configFactory) {
    $this->configFactory = $configFactory;
  }

  /**
   * {@inheritdoc}
   */
  public function get() {
    $settings = $this->configFactory->get('activenet.settings');
    $config = [
      'base_uri' => $settings->get('base_uri'),
      'headers'  => [
        'Accept' => 'application/json',
        'page_info' => '{"total_records_per_page":200}',
      ],
    ];
    $api_config = [
      'base_uri' => $settings->get('base_uri'),
      'api_key' => $settings->get('api_key'),
    ];
    $client = new ActivenetClient($config);
    $client->setApi($api_config);
    return $client;
  }

}
