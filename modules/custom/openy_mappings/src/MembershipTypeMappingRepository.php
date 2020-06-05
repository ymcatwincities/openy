<?php

namespace Drupal\openy_mappings;

use Drupal\Core\Entity\EntityTypeManagerInterface;

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
   * Mapping storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $storageMapping;

  /**
   * MembershipTypeMappingRepository constructor.
   *
   * @param EntityTypeManagerInterface $entityTypeManager
   *   Entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager) {
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
    $ids = $this->entityTypeManager
      ->getStorage('mapping')
      ->getQuery()
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
