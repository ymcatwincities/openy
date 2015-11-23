<?php

/**
 * @file
 * Contains \Drupal\crop\CropInterface.
 */

namespace Drupal\crop;

use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Provides an interface defining the crop entity.
 */
interface CropInterface extends ContentEntityInterface {
  /**
   * Gets position of crop's center.
   *
   * @return array
   *   Array with two keys (x, y) and center coordinates as values.
   */
  public function position();

  /**
   * Gets crop's size.
   *
   * @return array
   *   Array with two keys (width, height) each side dimensions as values.
   */
  public function size();

  /**
   * Gets crop anchor (top-left corner of crop area).
   *
   * @return array
   *   Array with two keys (x, y) and anchor coordinates as values.
   */
  public function anchor();

  /**
   * Gets entity provider for the crop.
   *
   * @return \Drupal\crop\EntityProviderInterface
   *   Entity provider.
   *
   * @throws \Drupal\crop\EntityProviderNotFoundException
   *   Thrown if entity provider not found.
   */
  public function provider();

}
