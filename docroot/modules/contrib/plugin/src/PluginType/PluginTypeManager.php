<?php

namespace Drupal\plugin\PluginType;

use Drupal\Component\Discovery\YamlDiscovery;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a plugin type manager.
 */
class PluginTypeManager implements PluginTypeManagerInterface {

  /**
   * The service container.
   *
   * @var \Symfony\Component\DependencyInjection\ContainerInterface
   */
  protected $container;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The known plugin types.
   *
   * @var \Drupal\plugin\PluginType\PluginTypeInterface[]|null
   *   An array of plugin types or NULL if plugin type discovery has not been
   *   executed yet.
   */
  protected $pluginTypes;

  /**
   * Creates a new instance.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The service container.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(ContainerInterface $container, ModuleHandlerInterface $module_handler) {
    $this->container = $container;
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public function hasPluginType($id) {
    return isset($this->getPluginTypes()[$id]);
  }

  /**
   * {@inheritdoc}
   */
  public function getPluginType($id) {
    $plugin_types = $this->getPluginTypes();
    if (isset($plugin_types[$id])) {
      return $plugin_types[$id];
    }
    else {
      throw new \InvalidArgumentException(sprintf('Plugin type "%s" is unknown.', $id));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getPluginTypes() {
    if (is_null($this->pluginTypes)) {
      $this->pluginTypes = [];

      // Get the plugin type definitions.
      $plugin_types_data_discovery = new YamlDiscovery('plugin_type', $this->moduleHandler->getModuleDirectories());
      $plugin_type_definitions_by_module = $plugin_types_data_discovery->findAll();

      // For every definition, set defaults and instantiate an object.
      foreach ($plugin_type_definitions_by_module as $module => $plugin_type_definitions) {
        $plugin_type_definition_defaults = [
          'provider' => $module
        ];
        foreach ($plugin_type_definitions as $plugin_type_id => $plugin_type_definition) {
          $plugin_type_definition += $plugin_type_definition_defaults;
          if ($plugin_type_definition['provider'] == 'core' || $this->moduleHandler->moduleExists($plugin_type_definition['provider'])) {
            $plugin_type_definition['id'] = $plugin_type_id;
            /** @var \Drupal\plugin\PluginType\PluginTypeInterface $class */
            $class = isset($plugin_type_definition['class']) ? $plugin_type_definition['class'] : PluginType::class;
            $plugin_type = $class::createFromDefinition($this->container, $plugin_type_definition);
            $this->pluginTypes[$plugin_type_id] = $plugin_type;
          }
        }
      }
    }

    return $this->pluginTypes;
  }

}
