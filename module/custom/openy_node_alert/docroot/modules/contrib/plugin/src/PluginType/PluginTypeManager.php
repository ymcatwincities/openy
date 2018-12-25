<?php

namespace Drupal\plugin\PluginType;

use Drupal\Component\FileCache\FileCacheFactory;
use Drupal\Component\Serialization\Yaml;
use Drupal\Core\Extension\Extension;
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
    // Return immediately if all data is available in the static cache.
    if (is_array($this->pluginTypes)) {
      return $this->pluginTypes;
    }

    $this->pluginTypes = [];

    // \Drupal\Component\Discovery\YamlDiscovery::findAll() caches raw file
    // contents, but we want to cache plugin types for better performance.
    $files = $this->findFiles();
    $providers_by_file = array_flip($files);
    $file_cache = FileCacheFactory::get('plugin:plugin_type');

    // Try to load from the file cache first.
    foreach ($file_cache->getMultiple($files) as $file => $plugin_types_by_file) {
      $this->pluginTypes = array_merge($this->pluginTypes, $plugin_types_by_file);
      unset($providers_by_file[$file]);
    }

    // List the available plugin type providers.
    $providers = array_map(function(Extension $module) {
      return $module->getName();
    }, $this->moduleHandler->getModuleList());
    $providers[] = 'core';

    // If there are files left that were not returned from the cache, load and
    // parse them now. This list was flipped above and is keyed by filename.
    foreach ($providers_by_file as $file => $provider) {
      // If a file is empty or its contents are commented out, return an empty
      // array instead of NULL for type consistency.
      $plugin_type_definitions = Yaml::decode(file_get_contents($file)) ?: [];

      // Set the plugin type definitions' default values.
      $plugin_type_definition_defaults = [
        'class' => PluginType::class,
        'provider' => $provider,
      ];
      $plugin_type_definitions = array_map(function($plugin_type_id, array $plugin_type_definition) use ($plugin_type_definition_defaults) {
        $plugin_type_definition['id'] = $plugin_type_id;
        return $plugin_type_definition + $plugin_type_definition_defaults;
      }, array_keys($plugin_type_definitions), $plugin_type_definitions);

      // Remove definitions from uninstalled providers.
      $plugin_type_definitions = array_filter($plugin_type_definitions, function(array $plugin_type_definition) use ($providers) {
        return in_array($plugin_type_definition['provider'], $providers);
      });

      // Create plugin types from their definitions.
      $file_plugin_types = [];
      foreach ($plugin_type_definitions as $plugin_type_definition) {
        /** @var \Drupal\plugin\PluginType\PluginTypeInterface $class */
        $class = $plugin_type_definition['class'];
        $plugin_type= $class::createFromDefinition($this->container, $plugin_type_definition);
        $file_plugin_types[$plugin_type->getId()] = $plugin_type;
      }

      // Store the plugin types in the static and file caches.
      $this->pluginTypes += $file_plugin_types;
      $file_cache->set($file, $file_plugin_types);
    }

    return $this->pluginTypes;
  }

  /**
   * Returns an array of file paths, keyed by provider.
   *
   * @return string[]
   */
  protected function findFiles() {
    $files = [];
    foreach ($this->moduleHandler->getModuleDirectories() as $provider => $directory) {
      $file = $directory . '/' . $provider . '.plugin_type.yml';
      if (file_exists($file)) {
        $files[$provider] = $file;
      }
    }
    return $files;
  }

}
