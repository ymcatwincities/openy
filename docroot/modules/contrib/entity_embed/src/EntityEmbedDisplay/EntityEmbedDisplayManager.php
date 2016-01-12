<?php

/**
 * @file
 * Contains \Drupal\entity_embed\EntityEmbedDisplay\EntityEmbedDisplayManager.
 */

namespace Drupal\entity_embed\EntityEmbedDisplay;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Component\Plugin\Exception\PluginException;

/**
 * Provides an Entity Embed display plugin manager.
 *
 * @see \Drupal\entity_embed\Annotation\EntityEmbedDisplay
 * @see \Drupal\entity_embed\EntityEmbedDisplay\EntityEmbedDisplayBase
 * @see \Drupal\entity_embed\EntityEmbedDisplay\EntityEmbedDisplayInterface
 * @see plugin_api
 */
class EntityEmbedDisplayManager extends DefaultPluginManager {

  /**
   * Constructs a new EntityEmbedDisplayManager.
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
    parent::__construct('Plugin/entity_embed/EntityEmbedDisplay', $namespaces, $module_handler, 'Drupal\entity_embed\EntityEmbedDisplay\EntityEmbedDisplayInterface', 'Drupal\entity_embed\Annotation\EntityEmbedDisplay');
    $this->alterInfo('entity_embed_display_plugins');
    $this->setCacheBackend($cache_backend, 'entity_embed_display_plugins');
  }

  /**
   * Overrides DefaultPluginManager::processDefinition().
   */
  public function processDefinition(&$definition, $plugin_id) {
    $definition += array(
      'entity_types' => FALSE,
    );

    if ($definition['entity_types'] !== FALSE && !is_array($definition['entity_types'])) {
      $definition['entity_types'] = array($definition['entity_types']);
    }
  }

  /**
   * Determines plugins whose constraints are satisfied by a set of contexts.
   *
   * @param array $contexts
   *   An array of contexts.
   *
   * @return array
   *   An array of plugin definitions.
   *
   * @todo At some point convert this to use ContextAwarePluginManagerTrait
   * @see https://drupal.org/node/2277981
   */
  public function getDefinitionsForContexts(array $contexts = array()) {
    $definitions = $this->getDefinitions();
    $valid_ids = array_filter(array_keys($definitions), function ($id) use ($contexts) {
      try {
        $display = $this->createInstance($id);
        foreach ($contexts as $name => $value) {
          $display->setContextValue($name, $value);
        }
        return $display->access();
      }
      catch (PluginException $e) {
        return FALSE;
      }
    });
    $definitions_for_context = array_intersect_key($definitions, array_flip($valid_ids));
    $this->moduleHandler->alter('entity_embed_display_plugins_for_context', $definitions_for_context, $contexts);
    return $definitions_for_context;
  }

  /**
   * Provides a list of plugins that can be used for a certain entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   An entity object.
   *
   * @return array
   *   An array of valid plugin labels, keyed by plugin ID.
   */
  public function getDefinitionOptionsForEntity(EntityInterface $entity) {
    $definitions = $this->getDefinitionsForContexts(array('entity' => $entity, 'entity_type' => $entity->getEntityTypeId()));
    return array_map(function ($definition) {
      return (string) $definition['label'];
    }, $definitions);
  }

  /**
   * Provides a list of plugins that can be used for a certain entity type.
   *
   * @param string $entity_type
   *   The entity type id.
   *
   * @return array
   *   An array of valid plugin labels, keyed by plugin ID.
   */
  public function getDefinitionOptionsForEntityType($entity_type) {
    $definitions = $this->getDefinitionsForContexts(array('entity_type' => $entity_type));
    return array_map(function ($definition) {
      return (string) $definition['label'];
    }, $definitions);
  }

}
