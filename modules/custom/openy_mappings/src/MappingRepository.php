<?php

namespace Drupal\openy_mappings;

use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Class MappingRepository.
 */
class MappingRepository {

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
   * MappingRepository constructor.
   *
   * @param EntityTypeManagerInterface $entityTypeManager
   *   Entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager) {
    $this->entityTypeManager = $entityTypeManager;
    $this->storageMapping = $this->entityTypeManager->getStorage('mapping');
  }

  /**
   * Delete all mappings by type.
   *
   * @param string $type
   *   Mapping type.
   * @param int $chunk_size
   *   Chunk size.
   */
  public function deleteAllMappingsByType($type, $chunk_size = 25) {
    $ids = $this->entityTypeManager
      ->getStorage('mapping')
      ->getQuery()
      ->condition('type', $type)
      ->execute();

    if (!$ids) {
      return;
    }

    $chunks = array_chunk($ids, $chunk_size);
    foreach ($chunks as $chunk) {
      $entities = $this->storageMapping->loadMultiple($chunk);
      $this->storageMapping->delete($entities);
    }
  }

}
