<?php

namespace Drupal\ymca_cdn_product;

use Drupal\ymca_cdn_product\Entity\CdnPrsProduct;
use Drupal\ymca_cdn_product\Entity\CdnPrsProductInterface;

/**
 * Interface CdnPrsProductRepositoryInterface.
 *
 * @package Drupal\ymca_cdn_product
 */
interface CdnPrsProductRepositoryInterface {

  /**
   * Remove all entities.
   */
  public function removeAll();

  /**
   * Create product entity.
   *
   * @param \SimpleXMLElement $product
   *   Entity data.
   *
   * @return CdnPrsProductInterface
   *   Product.
   */
  public function createEntity(\SimpleXMLElement $product);

  /**
   * Get Product by Personify ID.
   *
   * @param int $id
   *   ID.
   *
   * @return CdnPrsProduct
   *   Product.
   */
  public function getProductByPersonifyProductId($id);

  /**
   * Get Product hash.
   *
   * @param \SimpleXMLElement $xmlProduct
   *   Product.
   *
   * @return string
   *   MD5 Hash.
   */
  public function getProductHash(\SimpleXMLElement $xmlProduct);

  /**
   * Remove product.
   *
   * @param int $id
   *   Entity ID.
   */
  public function removeProduct($id);

}
