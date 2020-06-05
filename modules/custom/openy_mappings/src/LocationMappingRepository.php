<?php

namespace Drupal\openy_mappings;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Field\FieldItemList;
use Drupal\openy_mappings\Entity\Mapping;
use Drupal\Core\Entity\Query\QueryInterface;

/**
 * Class LocationMappingRepository.
 */
class LocationMappingRepository {

  /**
   * Mapping type.
   */
  const TYPE = 'location';

  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Mapping storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $storage;

  /**
   * MappingRepository constructor.
   *
   * @param EntityTypeManagerInterface $entityTypeManager
   *   Entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager) {
    $this->entityTypeManager = $entityTypeManager;
    $this->storage = $this->entityTypeManager->getStorage('mapping');
  }

  /**
   * Get branch by Daxko branch ID.
   *
   * @param int $id
   *   Daxko branch ID.
   *
   * @return \Drupal\node\NodeInterface
   *   Branch node.
   */
  public function getBranchByDaxkoBranchId($id) {
    $cache = &drupal_static(__FUNCTION__);

    if (!isset($cache[$id])) {
      $ids = $this->entityTypeManager
        ->getStorage('mapping')
        ->getQuery()
        ->condition('type', 'branch')
        ->condition('field_daxko_branch_id', $id)
        ->execute();

      if (!$ids) {
        return NULL;
      }

      // Get mapping.
      $mapping_storage = $this->entityTypeManager->getStorage('mapping');
      $mapping = $mapping_storage->load(reset($ids));

      // Get node.
      $node_id = $mapping->field_ref_branch_id->target_id;
      $node_storage = $this->entityTypeManager->getStorage('node');
      $cache[$id] = $node_storage->load($node_id);
    }

    return $cache[$id];
  }

  /**
   * Get all available Daxko branch IDs.
   *
   * @return array
   *   The list of branch IDs.
   */
  public function getAllDaxkoBranchIds() {
    $daxko_ids = [];

    $ids = $this->entityTypeManager
      ->getStorage('mapping')
      ->getQuery()
      ->condition('type', 'branch')
      ->execute();

    if (!$ids) {
      return [];
    }

    $storage = $this->entityTypeManager->getStorage('mapping');
    $entities = $storage->loadMultiple($ids);
    foreach ($entities as $entity) {
      $daxko_ids[] = $entity->field_daxko_branch_id->value;
    }

    return $daxko_ids;
  }

  /**
   * Load all location mappings where GroupEx ID is present.
   *
   * @return array
   *   An array of found location mapping objects sorted by name.
   */
  public function loadAllLocationsWithGroupExId() {
    $mapping_ids = $this->entityTypeManager
      ->getStorage('mapping')
      ->getQuery()
      ->condition('type', self::TYPE)
      ->condition('field_groupex_id', 0, '>')
      ->sort('name', 'ASC')
      ->execute();
    if (!$mapping_ids) {
      return [];
    }

    return $this->storage->loadMultiple($mapping_ids);
  }

}
