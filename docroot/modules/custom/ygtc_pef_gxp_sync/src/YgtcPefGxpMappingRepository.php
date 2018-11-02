<?php

namespace Drupal\ygtc_pef_gxp_sync;

use Drupal\Core\Entity\EntityTypeManager;

/**
 * Class YgtcPefGxpMappingRepository.
 *
 * @package Drupal\ygtc_pef_gxp_sync
 */
class YgtcPefGxpMappingRepository {

  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * YgtcPefGxpMappingRepository constructor.
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
      ->getStorage('ygtc_pef_gxp_mapping')
      ->loadByProperties(['product_id' => $productId]);
  }

}
