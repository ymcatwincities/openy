<?php

namespace Drupal\openy_pef_gxp_sync;

use Drupal\Core\Entity\EntityTypeManager;

/**
 * Class OpenYPefGxpMappingRepository.
 *
 * @package Drupal\openy_pef_gxp_sync
 */
class OpenYPefGxpMappingRepository {

  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * OpenYPefGxpMappingRepository constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManager $entityTypeManager
   *   Entity type manager.
   */
  public function __construct(EntityTypeManager $entityTypeManager) {
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * Get mapping items by product id.
   *
   * @param string $productId
   *   Product ID.
   *
   * @return \Drupal\Core\Entity\EntityInterface[]
   *   The list of founded items.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  public function getMappingByProductId($productId) {
    return $this->entityTypeManager
      ->getStorage('openy_pef_gxp_mapping')
      ->loadByProperties(['product_id' => $productId]);
  }

  /**
   * Get mappings by hash.
   *
   * @param string $hash
   *   Hash of the mapping entity.
   *
   * @return \Drupal\Core\Entity\EntityInterface[]
   *     The list of items found.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  public function getMappingByHash($hash) {
    return $this->entityTypeManager
      ->getStorage('openy_pef_gxp_mapping')
      ->loadByProperties(['hash' => $hash]);
  }

}
