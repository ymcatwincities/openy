<?php

namespace Drupal\rabbit_hole\Plugin;

use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Provides the Rabbit hole entity plugin plugin manager.
 */
class RabbitHoleEntityPluginManager extends DefaultPluginManager {

  /**
   * Drupal\Core\Entity\EntityTypeManagerInterface.
   */
  private $etm;

  /**
   * Constructor for RabbitHoleEntityPluginManager objects.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler,
    EntityTypeManagerInterface $etm) {
    parent::__construct('Plugin/RabbitHoleEntityPlugin', $namespaces, $module_handler, 'Drupal\rabbit_hole\Plugin\RabbitHoleEntityPluginInterface', 'Drupal\rabbit_hole\Annotation\RabbitHoleEntityPlugin');

    $this->alterInfo('rabbit_hole_rabbit_hole_entity_plugin_info');
    $this->setCacheBackend($cache_backend, 'rabbit_hole_rabbit_hole_entity_plugin_plugins');

    $this->etm = $etm;
  }

  /**
   * Create an instance of the first plugin found with string id $entity_type.
   *
   * Create an instance of the first plugin found supporting the entity type
   * with string id $entity_type.
   *
   * @param string $entity_type
   *   The string ID of the entity type.
   *
   * @return Drupal\rabbit_hole\Plugin\RabbitHoleEntityPluginInterface
   *   The plugin.
   */
  public function createInstanceByEntityType($entity_type) {
    $plugin_ids = array_keys($this->loadDefinitionsByEntityType($entity_type));
    return $this->createInstance($plugin_ids[0]);
  }

  /**
   * Load plugins implementing entity with id $entity_type.
   *
   * @param string $entity_type
   *   The string ID of the entity type.
   *
   * @return array
   *   An array of plugin definitions for the entity type with ID $entity_type.
   */
  public function loadDefinitionsByEntityType($entity_type) {
    return array_filter($this->getDefinitions(), function($var) use ($entity_type) {
      return $var['entityType'] == $entity_type;
    });
  }

  /**
   * Load the string IDs for the supported entity types.
   *
   * @return array
   *   An array of entity type ID strings.
   */
  public function loadSupportedEntityTypes() {
    return array_values(array_map(function($var) {
      return $var['entityType'];
    }, $this->getDefinitions()));
  }

  /**
   * Load the string IDs for the supported bundle entity types.
   *
   * @return array
   *   An array of entity type ID strings.
   */
  public function loadSupportedBundleEntityTypes() {
    return array_values(array_map(function($var) {
      return $this->etm->getStorage($var['entityType'])
        ->getEntityType()->getBundleEntityType();
    }, $this->getDefinitions()));
  }

  /**
   * Load the string IDs for the global configuration forms for entity types.
   *
   * @return array
   *   An array of entity types and form ID strings in the form
   *   form_id => entity_type.
   */
  public function loadSupportedGlobalForms() {
    $result = array();
    foreach ($this->getDefinitions() as $key => $def) {
      $form_id = $this->createInstance($key)->getGlobalConfigFormId();
      if (isset($form_id)) {
        $result[$form_id] = $def['entityType'];
      }
    }
    return $result;
  }

  /**
   * Load a map of tokens per entity type.
   *
   * Used for behavior plugins that use tokens like PageRedirect.
   * @return array
   *  An array of token IDs keyed by entity ID
   */
  public function loadEntityTokenMap() {
    $map = array();
    foreach ($this->getDefinitions() as $key => $def) {
      $map += $this->createInstance($key)->getEntityTokenMap();
    }
    return $map;
  }

}
