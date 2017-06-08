<?php

namespace Drupal\plugin\PluginManager;

use Drupal\Component\Plugin\Discovery\DiscoveryInterface;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\plugin\PluginDiscovery\PluginDiscoveryDecorator;

/**
 * Provides a plugin manager decorator.
 */
class PluginManagerDecorator extends PluginDiscoveryDecorator implements PluginManagerInterface {

  /**
   * The decorated plugin factory.
   *
   * @var \Drupal\Component\Plugin\PluginManagerInterface
   */
  protected $decoratedFactory;

  /**
   * Creates a new instance.
   *
   * @param \Drupal\Component\Plugin\PluginManagerInterface $plugin_manager
   *   The decorated plugin manager.
   * @param \Drupal\Component\Plugin\Discovery\DiscoveryInterface|null $discovery
   *   A plugin discovery to use instead of the decorated plugin manager, or
   *   NULL to use the decorated plugin manager.
   */
  public function __construct(PluginManagerInterface $plugin_manager, DiscoveryInterface $discovery = NULL) {
    $this->decoratedDiscovery = $discovery ? $discovery : $plugin_manager;
    $this->decoratedFactory = $plugin_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function createInstance($plugin_id, array $configuration = []) {
    if ($this->hasDefinition($plugin_id)) {
      return $this->decoratedFactory->createInstance($plugin_id, $configuration);
    }
    else {
      throw new PluginNotFoundException($plugin_id);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getInstance(array $options) {
    throw new \BadMethodCallException('This method is not supported. See https://www.drupal.org/node/1894130.');
  }

}
