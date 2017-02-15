<?php

namespace Drupal\openy_mappings;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryFactory;

/**
 * Class MembershipTypeMappingRepository.
 */
class MembershipTypeMappingRepository {

  const TYPE = 'membership_type';

  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Query factory.
   *
   * @var \Drupal\Core\Entity\Query\QueryFactory
   */
  protected $queryFactory;

  /**
   * Mapping storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $storageMapping;

  /**
   * MembershipTypeMappingRepository constructor.
   *
   * @param QueryFactory $query_factory
   *   Query factory.
   * @param EntityTypeManagerInterface $entityTypeManager
   *   Entity type manager.
   */
  public function __construct(QueryFactory $query_factory, EntityTypeManagerInterface $entityTypeManager) {
    $this->queryFactory = $query_factory;
    $this->entityTypeManager = $entityTypeManager;
    $this->storageMapping = $this->entityTypeManager->getStorage('mapping');
  }

  /**
   * Get mapping by name and branch ID.
   *
   * @param string $name
   *   Name.
   * @param int $branch_id
   *   Branch ID.
   *
   * @return \Drupal\openy_mappings\Entity\Mapping
   *   Mapping entity.
   */
  public function getMappingByNameAndBranch($name, $branch_id) {
    $ids = $this->queryFactory
      ->get('mapping')
      ->condition('type', self::TYPE)
      ->condition('name', $name)
      ->condition('field_branch_ct_reference', $branch_id)
      ->execute();

    if (!$ids) {
      return NULL;
    }

    $ids = reset($ids);
    return $this->storageMapping->load($ids);
  }

}
