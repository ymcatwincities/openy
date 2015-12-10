<?php

/**
 * @file
 * Contains ComponentManager.
 */

namespace Drupal\webform;

use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * Component plugin manager.
 */
class ComponentManager extends DefaultPluginManager {

  /**
   * Constructs the ComponentManager object.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/WebformComponent', $namespaces, $module_handler, 'Drupal\webform\ComponentInterface', 'Drupal\webform\Annotation\Component');

    $this->alterInfo('webform_component_info');
    $this->setCacheBackend($cache_backend, 'webform_component');
  }

  /**
   * Returns a list of component plugins.
   *
   * @return array
   *   List of component plugins keyed by ID.
   */
  public function componentList() {
    $component_list = array();
    $component_types = $this->getDefinitions();
    foreach ($component_types as $key => $component_type) {
      $component = $this->createInstance($component_type['id']);
      $component_list[$key] = $component->getLabel();
    }

    return $component_list;
  }

}
