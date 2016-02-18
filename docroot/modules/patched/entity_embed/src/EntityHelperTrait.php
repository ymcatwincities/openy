<?php

/**
 * @file
 * Contains Drupal\entity_embed\EntityHelperTrait.
 */

namespace Drupal\entity_embed;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\entity_embed\EntityEmbedDisplay\EntityEmbedDisplayManager;

/**
 * Wrapper methods for entity loading and rendering.
 *
 * This utility trait should only be used in application-level code, such as
 * classes that would implement ContainerInjectionInterface. Services registered
 * in the Container should not use this trait but inject the appropriate service
 * directly for easier testing.
 */
trait EntityHelperTrait {

  /**
   * The entity manager service.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * The module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface.
   */
  protected $moduleHandler;

  /**
   * The Entity Embed Display plugin manager.
   *
   * @var \Drupal\entity_embed\EntityEmbedDisplay\EntityEmbedDisplayManager.
   */
  protected $displayPluginManager;

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface.
   */
  protected $renderer;

  /**
   * Loads an entity from the database.
   *
   * @param string $entity_type
   *   The entity type to load, e.g. node or user.
   * @param mixed $id
   *   The id or UUID of the entity to load.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   The entity object, or NULL if there is no entity with the given id or
   *   UUID.
   */
  protected function loadEntity($entity_type, $id) {
    $entities = $this->loadMultipleEntities($entity_type, array($id));
    return !empty($entities) ? reset($entities) : NULL;
  }

  /**
   * Loads multiple entities from the database.
   *
   * @param string $entity_type
   *   The entity type to load, e.g. node or user.
   * @param array $ids
   *   An array of entity IDs or UUIDs.
   *
   * @return array
   *   An array of entity objects indexed by their ids.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   *   Throws an exception if the entity type does not supports UUIDs.
   */
  protected function loadMultipleEntities($entity_type, array $ids) {
    $entities = array();
    $storage = $this->entityManager()->getStorage($entity_type);

    $uuids = array_filter($ids, 'Drupal\Component\Uuid\Uuid::isValid');
    if (!empty($uuids)) {
      $definition = $this->entityManager()->getDefinition($entity_type);
      if (!$uuid_key = $definition->getKey('uuid')) {
        throw new EntityStorageException("Entity type $entity_type does not support UUIDs.");
      }
      $entities += $storage->loadByProperties(array($uuid_key => $uuids));
    }

    if ($remaining_ids = array_diff($ids, $uuids)) {
      $entities += $storage->loadMultiple($remaining_ids);
    }

    return $entities;
  }

  /**
   * Determines if an entity can be rendered.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity object.
   *
   * @return bool
   *   TRUE if the entity's type has a view builder controller, otherwise FALSE.
   */
  protected function canRenderEntity(EntityInterface $entity) {
    $entity_type = $entity->getEntityTypeId();
    return $this->canRenderEntityType($entity_type);
  }

  /**
   * Determines if an entity type can be rendered.
   *
   * @param string $entity_type
   *   The entity type id.
   *
   * @return bool
   *   TRUE if the entitys type has a view builder controller, otherwise FALSE.
   */
  protected function canRenderEntityType($entity_type) {
    return $this->entityManager()->hasHandler($entity_type, 'view_builder');
  }

  /**
   * Returns the render array for an entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to be rendered.
   * @param string $view_mode
   *   The view mode that should be used to display the entity.
   * @param string $langcode
   *   (optional) For which language the entity should be rendered, defaults to
   *   the current content language.
   *
   * @return array
   *   A render array for the entity.
   */
  protected function renderEntity(EntityInterface $entity, $view_mode, $langcode = NULL) {
    $render_controller = $this->entityManager()->getViewBuilder($entity->getEntityTypeId());
    return $render_controller->view($entity, $view_mode, $langcode);
  }

  /**
   * Renders an embedded entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to be rendered.
   * @param array $context
   *   (optional) Array of context values, corresponding to the attributes on
   *   the embed HTML tag.
   *
   * @return string
   *   The HTML of the entity rendered with the Entity Embed Display plugin.
   */
  protected function renderEntityEmbed(EntityInterface $entity, array $context = array()) {
    // Support the deprecated view-mode data attribute.
    if (isset($context['data-view-mode']) && !isset($context['data-entity-embed-display']) && !isset($context['data-entity-embed-settings'])) {
      $context['data-entity-embed-display'] = 'entity_reference:entity_reference_entity_view';
      $context['data-entity-embed-settings'] = ['view_mode' => &$context['data-view-mode']];
    }

    // Merge in default attributes.
    $context += array(
      'data-entity-id' => $entity->id(),
      'data-entity-type' => $entity->getEntityTypeId(),
      'data-entity-embed-display' => 'entity_reference:entity_reference_entity_view',
      'data-entity-embed-settings' => array(),
    );

    // The default Entity Embed Display plugin has been deprecated by the
    // rendered entity field formatter.
    if ($context['data-entity-embed-display'] === 'default') {
      $context['data-entity-embed-display'] = 'entity_reference:entity_reference_entity_view';
    }

    // Allow modules to alter the entity prior to embed rendering.
    $this->moduleHandler()->alter(array("{$context['data-entity-type']}_embed_context", 'entity_embed_context'), $context, $entity);

    // Build and render the Entity Embed Display plugin, allowing modules to
    // alter the result before rendering.
    $build = $this->renderEntityEmbedDisplayPlugin(
      $entity,
      $context['data-entity-embed-display'],
      $context['data-entity-embed-settings'],
      $context
    );
    // @todo Should this hook get invoked if $build is an empty array?
    $this->moduleHandler()->alter(array("{$context['data-entity-type']}_embed", 'entity_embed'), $build, $entity, $context);
    $entity_output = $this->renderer()->render($build);

    return $entity_output;
  }

  /**
   * Renders an entity using an Entity Embed Display plugin.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to be rendered.
   * @param string $plugin_id
   *   The Entity Embed Display plugin ID.
   * @param array $plugin_configuration
   *   (optional) Array of plugin configuration values.
   * @param array $context
   *   (optional) Array of additional context values, usually the embed HTML
   *   tag's attributes.
   *
   * @return array
   *   A render array for the Entity Embed Display plugin.
   */
  protected function renderEntityEmbedDisplayPlugin(EntityInterface $entity, $plugin_id, array $plugin_configuration = array(), array $context = array()) {
    // Build the Entity Embed Display plugin.
    /** @var \Drupal\entity_embed\EntityEmbedDisplay\EntityEmbedDisplayBase $display */
    $display = $this->displayPluginManager()->createInstance($plugin_id, $plugin_configuration);
    $display->setContextValue('entity', $entity);
    $display->setAttributes($context);

    // Check if the Entity Embed Display plugin is accessible. This also checks
    // entity access, which is why we never call $entity->access() here.
    if (!$display->access()) {
      return array();
    }

    return $display->build();
  }

  /**
   * Returns the entity manager.
   *
   * @return \Drupal\Core\Entity\EntityManagerInterface
   *   The entity manager.
   */
  protected function entityManager() {
    if (!isset($this->entityManager)) {
      $this->entityManager = \Drupal::entityManager();
    }
    return $this->entityManager;
  }

  /**
   * Sets the entity manager service.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager service.
   *
   * @return self
   */
  public function setEntityManager(EntityManagerInterface $entity_manager) {
    $this->entityManager = $entity_manager;
    return $this;
  }

  /**
   * Returns the module handler.
   *
   * @return \Drupal\Core\Extension\ModuleHandlerInterface
   *   The module handler.
   */
  protected function moduleHandler() {
    if (!isset($this->moduleHandler)) {
      $this->moduleHandler = \Drupal::moduleHandler();
    }
    return $this->moduleHandler;
  }

  /**
   * Sets the module handler service.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler service.
   *
   * @return self
   */
  public function setModuleHandler(ModuleHandlerInterface $module_handler) {
    $this->moduleHandler = $module_handler;
    return $this;
  }

  /**
   * Returns the Entity Embed Display plugin manager.
   *
   * @return \Drupal\entity_embed\EntityEmbedDisplay\EntityEmbedDisplayManager
   *   The Entity Embed Display plugin manager.
   */
  protected function displayPluginManager() {
    if (!isset($this->displayPluginManager)) {
      $this->displayPluginManager = \Drupal::service('plugin.manager.entity_embed.display');
    }
    return $this->displayPluginManager;
  }

  /**
   * Sets the Entity Embed Display plugin manager service.
   *
   * @param \Drupal\entity_embed\EntityEmbedDisplay\EntityEmbedDisplayManager $display_plugin_manager
   *   The Entity Embed Display plugin manager service.
   *
   * @return self
   */
  public function setDisplayPluginManager(EntityEmbedDisplayManager $display_plugin_manager) {
    $this->displayPluginManager = $display_plugin_manager;
    return $this;
  }

  /**
   * Returns the renderer.
   *
   * @return \Drupal\Core\Render\RendererInterface
   *   The renderer.
   */
  protected function renderer() {
    if (!isset($this->renderer)) {
      $this->renderer = \Drupal::service('renderer');
    }
    return $this->renderer;
  }

  /**
   * Sets the renderer.
   *
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer.
   *
   * @return self
   */
  public function setRenderer(RendererInterface $renderer) {
    $this->renderer = $renderer;
    return $this;
  }
}
