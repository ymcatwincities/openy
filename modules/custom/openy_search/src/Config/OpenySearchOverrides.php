<?php

namespace Drupal\openy_search\Config;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Config\ConfigFactoryOverrideInterface;
use Drupal\Core\Config\StorageInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * OpenY Search configuration override.
 */
class OpenySearchOverrides implements ConfigFactoryOverrideInterface {

  /**
   * Configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  private $configFactory;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  private $moduleHandler;

  /**
   * Constructs a DomainConfigSubscriber object.
   *
   * @param \Drupal\Core\Config\ConfigFactory $config_factory
   *   Configuration factory.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   Module handler.
   */
  public function __construct(ConfigFactory $config_factory, ModuleHandlerInterface $module_handler) {
    $this->configFactory = $config_factory;
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public function loadOverrides($names) {
    $default_theme = $this->configFactory->getEditable('system.theme')->getOriginal('default');
    $overrides = [];
    if ($this->moduleHandler->moduleExists('openy_google_search')) {
      if (in_array($default_theme . '.settings', $names)) {
        $overrides[$default_theme . '.settings'] = [
          'search_query_key' => 'q',
          'search_page_alias' => 'search',
          'display_search_form' => 1,
        ];
      }
    }
    return $overrides;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheSuffix() {
    return 'GoogleSearchOverrider';
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheableMetadata($name) {
    return new CacheableMetadata();
  }

  /**
   * {@inheritdoc}
   */
  public function createConfigObject($name, $collection = StorageInterface::DEFAULT_COLLECTION) {
    return NULL;
  }

}
