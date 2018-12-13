<?php

namespace Drupal\entity_clone\EntityClone\Content;

use Drupal\Core\Entity\EntityInterface;

/**
 * Class ContentEntityCloneBase.
 */
class UserEntityClone extends ContentEntityCloneBase {

  /**
   * {@inheritdoc}
   */
  public function cloneEntity(EntityInterface $entity, EntityInterface $cloned_entity, $properties = []) {
    /** @var \Drupal\user\UserInterface $cloned_entity */
    $cloned_entity->set('name', $cloned_entity->getAccountName() . '_cloned');
    return parent::cloneEntity($entity, $cloned_entity, $properties);
  }

}
