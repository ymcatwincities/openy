<?php

namespace Drupal\openy_mappings;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryFactory;

/**
 * Class LocationMappingRepository.
 */
class LocationMappingRepository {

  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * MappingRepository constructor.
   *
   * @param QueryFactory $query_factory
   *   Query factory.
   * @param EntityTypeManagerInterface $entityTypeManager
   *   Entity type manager.
   */
  public function __construct(QueryFactory $query_factory, EntityTypeManagerInterface $entityTypeManager) {
    $this->queryFactory = $query_factory;
    $this->entityTypeManager = $entityTypeManager;
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
      $ids = $this->queryFactory
        ->get('mapping')
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

    $ids = $this->queryFactory
      ->get('mapping')
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

}
