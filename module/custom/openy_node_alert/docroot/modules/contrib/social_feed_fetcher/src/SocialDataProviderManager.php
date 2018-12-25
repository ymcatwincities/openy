<?php

namespace Drupal\social_feed_fetcher;

use Drupal\Component\Plugin\Factory\DefaultFactory;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\social_feed_fetcher\Annotation\SocialDataProvider;
use Drupal\social_feed_fetcher\SocailDataProviderInterface;


/**
 * Provides an NodeProcessor plugin manager.
 */
class SocialDataProviderManager extends DefaultPluginManager {

  /**
   * @var \Drupal\Core\Config\Config
   */
  protected $config;


  /**
   * {@inheritdoc}
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler,  ConfigFactoryInterface $configFactory) {
    parent::__construct(
      'Plugin/SocialDataProvider',
      $namespaces,
      $module_handler,
      SocailDataProviderInterface::class,
      SocialDataProvider::class
    );
    # hook_node_processor_info_alter();
    $this->alterInfo('social_data_provider_info');
    $this->setCacheBackend($cache_backend, 'social_data_provider');
    $this->factory = new DefaultFactory($this->getDiscovery());
    $this->config = $configFactory->getEditable('social_feed_fetcher.settings');
  }

  /**
   * {@inheritdoc}
   */
  public function createInstance($plugin_id, array $configuration = array()) {
    $instance = parent::createInstance($plugin_id, $configuration);
    $instance->setConfig($this->config);
    return $instance;
  }

}