<?php

namespace Drupal\daxko;

use Drupal\Core\Config\ConfigFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class DaxkoClientFactory.
 *
 * @package Drupal\daxko
 */
class DaxkoClientFactory implements DaxkoClientFactoryInterface {


  /**
   * Config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs a new Daxko Client Factory instance.
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
    $settings = $this->configFactory->get('daxko.settings');
    $url = $settings->get('base_uri') . $settings->get('client_id') . '/';
    $config = [
      'base_uri' => $url,
      'auth' => [$settings->get('user'), $settings->get('pass')],
      'headers'  => ['Accept' => 'application/json'],
    ];
    return new DaxkoClient($config);
  }

}
