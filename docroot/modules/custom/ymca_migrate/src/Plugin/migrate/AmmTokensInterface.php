<?php
/**
 * @file
 * Interface needs to be implemented by objects that need to deal with tokens.
 */

namespace Drupal\ymca_migrate\Plugin\migrate;

/**
 * Interface AmmTokensInterface.
 *
 * @package Drupal\ymca_migrate\Plugin\migrate
 */
interface AmmTokensInterface {

  /**
   * Method for getting asset IDs.
   *
   * @return array
   *   Array of Asset's IDs in terms of AMM database.
   */
  public function getAssetIds();

  /**
   * Method for getting asset IDs.
   *
   * @return array
   *   Array of Page's IDs in terms of AMM database.
   */
  public function getPageIds();

}
