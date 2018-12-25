<?php

namespace Drupal\simple_sitemap;

use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Entity;

/**
 * Class EntityHelper
 * @package Drupal\simple_sitemap
 */
class EntityHelper {

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * EntityHelper constructor.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager) {
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * Gets an entity's bundle name.
   *
   * @param \Drupal\Core\Entity\Entity $entity
   * @return string
   */
  public function getEntityInstanceBundleName(Entity $entity) {
    return $entity->getEntityTypeId() == 'menu_link_content'
      // Menu fix.
      ? $entity->getMenuName() : $entity->bundle();
  }

  /**
   * Gets the entity type id for a bundle.
   *
   * @param \Drupal\Core\Entity\Entity $entity
   * @return null|string
   */
  public function getBundleEntityTypeId(Entity $entity) {
    return $entity->getEntityTypeId() == 'menu'
      // Menu fix.
      ? 'menu_link_content' : $entity->getEntityType()->getBundleOf();
  }

  /**
   * Returns objects of entity types that can be indexed.
   *
   * @return array
   *   Objects of entity types that can be indexed by the sitemap.
   */
  public function getSitemapEntityTypes() {
    $entity_types = $this->entityTypeManager->getDefinitions();

    foreach ($entity_types as $entity_type_id => $entity_type) {
      if (!$entity_type instanceof ContentEntityTypeInterface
        || !method_exists($entity_type, 'getBundleEntityType')
        || !$entity_type->hasLinkTemplate('canonical')) {
        unset($entity_types[$entity_type_id]);
      }
    }
    return $entity_types;
  }

  /**
   * Checks whether an entity type does not provide bundles.
   *
   * @param string $entity_type_id
   * @return bool
   */
  public function entityTypeIsAtomic($entity_type_id) {
    // Menu fix.
    if ($entity_type_id == 'menu_link_content') {
      return FALSE;
    }

    $sitemap_entity_types = $this->getSitemapEntityTypes();
    if (isset($sitemap_entity_types[$entity_type_id])) {
      $entity_type = $sitemap_entity_types[$entity_type_id];
      if (empty($entity_type->getBundleEntityType())) {
        return TRUE;
      }
    }
    // todo: throw exception.
    return FALSE;
  }

}
