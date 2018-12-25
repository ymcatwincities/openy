<?php

namespace Drupal\ymca_cdn_product;

use Drupal\Core\Entity\EntityStorageInterface;

/**
 * Class CdnPrsRepositoryTrait.
 *
 * @package Drupal\openy_socrates
 */
trait CdnPrsRepositoryTrait {

  /**
   * Remove entities by splitting $ids array to smaller chunks.
   *
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *   Storage to be used for entity load.
   * @param array $ids
   *   Entity ids array.
   * @param int $chunkSize
   *   Chunk size.
   */
  public function removeAllByChunks(EntityStorageInterface $storage, array $ids, $chunkSize = 10) {
    // Set appropriate chunk size.
    $default_chunk_size = 10;
    if ((int) $chunkSize <= 0) {
      $chunkSize = $default_chunk_size;
    }
    if (empty($ids)) {
      return;
    }
    $chunks = array_chunk($ids, $chunkSize);
    foreach ($chunks as $chunk) {
      $entities = $storage->loadMultiple($chunk);
      $storage->delete($entities);
    }
  }

}
