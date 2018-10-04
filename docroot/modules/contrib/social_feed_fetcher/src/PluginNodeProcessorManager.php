<?php

namespace Drupal\social_feed_fetcher;

use Drupal\Component\Plugin\Factory\DefaultFactory;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\social_feed_fetcher\Annotation\PluginNodeProcessor;

/**
 * Provides an NodeProcessor plugin manager.
 */
class PluginNodeProcessorManager extends DefaultPluginManager {

  /**
   * @var \Drupal\Core\Config\Config
   */
  protected $config;

  /**
   * @var \Drupal\Core\Entity\EntityStorageInterface|mixed|object
   */
  protected $entityStorage;

  /**
   * {@inheritdoc}
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler,  ConfigFactoryInterface $configFactory, EntityTypeManager $entityTypeManager) {
    parent::__construct(
      'Plugin/NodeProcessor',
      $namespaces,
      $module_handler,
      PluginNodeProcessorPluginInterface::class,
      PluginNodeProcessor::class
    );
    # hook_node_processor_info_alter();
    $this->alterInfo('node_processor_info');
    $this->setCacheBackend($cache_backend, 'node_processor');
    $this->factory = new DefaultFactory($this->getDiscovery());
    $this->config = $configFactory->getEditable('social_feed_fetcher.settings');
    $this->entityStorage = $entityTypeManager->getStorage('node');
  }

  /**
   * {@inheritdoc}
   */
  public function createInstance($plugin_id, array $configuration = array()) {
    $instance = parent::createInstance($plugin_id, $configuration);
    $instance->setConfig($this->config);
    $instance->setStorage($this->entityStorage);
    return $instance;
  }

}