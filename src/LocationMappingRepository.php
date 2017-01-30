<?php

namespace Drupal\openy_mappings;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\node\NodeInterface;

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
   * @return NodeInterface
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
      $field_data = $mapping->get('field_ref_branch_id')->getValue();
      $node_id = $field_data[0]['target_id'];
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
      $field_data = $entity->get('field_daxko_branch_id')->getValue();
      $daxko_ids[] = $field_data[0]['value'];
    }

    return $daxko_ids;
  }

}
