<?php

namespace Drupal\plugin\PluginDiscovery;

use Drupal\Component\Plugin\Discovery\DiscoveryInterface;
use Drupal\plugin\PluginType\PluginTypeInterface;

/**
 * Provides plugin discovery that ensures all definitions implement
 * \Drupal\Component\Plugin\PluginDefinitionInterface.
 */
class TypedDefinitionEnsuringPluginDiscoveryDecorator extends PluginDiscoveryDecorator implements TypedDiscoveryInterface {

  /**
   * The type of the plugin definitions to decorate.
   *
   * @var \Drupal\plugin\PluginType\PluginTypeInterface
   */
  protected $pluginType;

  /**
   * Constructs a new instance.
   *
   * @param \Drupal\plugin\PluginType\PluginTypeInterface $plugin_type
   *   The plugin type of which to decorate definitions.
   * @param \Drupal\Component\Plugin\Discovery\DiscoveryInterface|NULL $decorated_discovery
   *   The decorated discovery, or NULL to use the plugin type's default
   *   discovery.
   */
  public function __construct(PluginTypeInterface $plugin_type, DiscoveryInterface $decorated_discovery = NULL) {
    parent::__construct($decorated_discovery ?: $plugin_type->getPluginManager());
    $this->pluginType = $plugin_type;
  }

  /**
   * {@inheritdoc}
   */
  public function processDecoratedDefinitions(array $decorated_plugin_definitions) {
    $processed_plugin_definitions = [];
    foreach ($decorated_plugin_definitions as $plugin_id => $decorated_plugin_definition) {
      $processed_plugin_definitions[$plugin_id] = $this->pluginType->ensureTypedPluginDefinition($decorated_plugin_definition);
    }

    return $processed_plugin_definitions;
  }

}
