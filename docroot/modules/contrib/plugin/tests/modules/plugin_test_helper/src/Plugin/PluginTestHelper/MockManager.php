<?php

namespace Drupal\plugin_test_helper\Plugin\PluginTestHelper;

use Drupal\Component\Plugin\Factory\DefaultFactory;
use Drupal\Component\Plugin\PluginManagerBase;
use Drupal\Component\Plugin\Discovery\StaticDiscovery;
use Drupal\plugin\PluginDefinition\ArrayPluginDefinitionDecorator;
use Drupal\plugin\PluginDiscovery\TypedDiscoveryInterface;

/**
 * Provides a plugin manager for testing plugin-related functionality.
 *
 * Configuration schemas for this manager's plugins are named
 * "plugin_test_helper.plugin_configuration.plugin_test_helper_mock.[plugin_id]",
 * where "[plugin_id]" is the ID of the plugin the schema is for.
 */
class MockManager extends PluginManagerBase implements TypedDiscoveryInterface {

  /**
   * Constructs a new instance.
   */
  public function __construct() {
    $this->discovery = new StaticDiscovery();

    $plugin_id = 'plugin_test_helper_plugin';
    $this->discovery->setDefinition($plugin_id, new ArrayPluginDefinitionDecorator([
      'id' => $plugin_id,
      'label' => t('Plugin'),
      'class' => MockPlugin::class,
    ]));

    $configurable_plugin_id = 'plugin_test_helper_configurable_plugin';
    $this->discovery->setDefinition($configurable_plugin_id, new ArrayPluginDefinitionDecorator([
      'id' => $configurable_plugin_id,
      'label' => t('Configurable plugin'),
      'class' => MockConfigurablePlugin::class ,
    ]));

    $this->factory = new DefaultFactory($this->discovery);
  }
}
