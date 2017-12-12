<?php

namespace Drupal\ymca_cdn_sync\syncer;

/**
 * Interface AddToCartInterface.
 *
 * @package Drupal\ymca_cdn_sync\syncer
 */
interface AddToCartInterface {

  /**
   * Add components into a shopping cart.
   *
   * @param int $user_id
   *   Personify user id.
   * @param array $product_ids
   *   Array of chosen products.
   */
  public function addToCart($user_id, $product_ids);

}
