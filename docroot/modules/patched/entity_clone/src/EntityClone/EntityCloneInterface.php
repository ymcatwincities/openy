<?php

namespace Drupal\entity_clone\EntityClone;

use Drupal\Core\Entity\EntityInterface;

/**
 * Defines a common interface for all entity clone objects.
 */
interface EntityCloneInterface {

  /**
   * Clone an entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity.
   * @param \Drupal\Core\Entity\EntityInterface $cloned_entity
   *   The cloned entity.
   * @param array $properties
   *   All new properties to replace old.
   *
   * @return \Drupal\Core\Entity\EntityInterface The new saved entity.
   * The new saved entity.
   */
  public function cloneEntity(EntityInterface $entity, EntityInterface $cloned_entity, $properties = []);

}
