<?php

namespace Drupal\entity_clone\EntityClone\Content;

use Drupal\Core\Entity\EntityHandlerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\entity_clone\EntityClone\EntityCloneInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class ContentEntityCloneBase.
 */
class ContentEntityCloneBase implements EntityHandlerInterface, EntityCloneInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * The entity type ID.
   *
   * @var string
   */
  protected $entityTypeId;

  /**
   * Constructs a new ContentEntityCloneBase.
   *
   * @param \Drupal\Core\Entity\EntityTypeManager $entity_type_manager
   *   The entity type manager.
   * @param string $entity_type_id
   *   The entity type ID.
   */
  public function __construct(EntityTypeManager $entity_type_manager, $entity_type_id) {
    $this->entityTypeManager = $entity_type_manager;
    $this->entityTypeId = $entity_type_id;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $container->get('entity_type.manager'),
      $entity_type->id()
    );
  }

  /**
   * {@inheritdoc}
   */
  public function cloneEntity(EntityInterface $entity, EntityInterface $cloned_entity, $properties = []) {
    /** @var \Drupal\core\Entity\ContentEntityInterface $cloned_entity */
    if ($label_key = $this->entityTypeManager->getDefinition($this->entityTypeId)->getKey('label')) {
      $cloned_entity->set($label_key, $entity->label() . ' - Cloned');
    }
    $cloned_entity->save();
    return $cloned_entity;
  }

}
