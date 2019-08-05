<?php

namespace Drupal\openy_moderation_wrapper;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * Evaluate an entities moderation status, and provide active moderation module.
 */
class EntityModerationStatus {

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Constructs EntityModerationStatus object.
   */
  public function __construct(ConfigFactoryInterface $config_factory, ModuleHandlerInterface $module_handler) {
    $this->configFactory = $config_factory;
    $this->moduleHandler = $module_handler;
  }

  /**
   * Get active moderation module.
   *
   * @return string
   */
  public function active_moderation_module() {
    $config = $this->configFactory->get('openy_moderation_wrapper.settings');
    return $config->get('moderation_module');
  }

  /**
   * Check entity moderation status.
   *
   * This is helper method with content moderation status checking.
   * If in the future content moderation will be replace workbench_moderation,
   * please update openy_moderation_wrapper.settings -> moderation_module
   * configuration.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   Entity.
   *
   * @return bool
   *   TRUE if entity is published.
   */
  public function entity_moderation_status(EntityInterface $entity) {
    $moderation_module = $this->active_moderation_module();
    if ($this->moduleHandler->moduleExists($moderation_module)) {
      $moderation_info = \Drupal::service($moderation_module . '.moderation_information');
      // Check that entity bundle support moderation.
      if ($moderation_info->getWorkflowForEntity($entity)) {
        // Contains checking:
        // isLatestRevision, isDefaultRevision, isPublishedState.
        return $moderation_info->isLiveRevision($entity);
      }
    }

    // Fallback to is isPublished & isDefaultRevision.
    return ($entity->isPublished() && $entity->isDefaultRevision());
  }

  /**
   * Returns true if the entity has changed its state.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity.
   *
   * @return bool
   *   Flag indicated the entity has changed its state.
   */
  public function entity_moderation_state_change(EntityInterface $entity) {
    if (!$original = $entity->original) {
      return FALSE;
    }

    // Any non default revisions don't change entity state.
    if (!$entity->isDefaultRevision()) {
      return FALSE;
    }

    // The entity published state changed.
    return $original->isPublished() != $entity->isPublished();
  }

}
