<?php

namespace Drupal\openy;

/**
 * Interface ContentImporterInterface.
 *
 * @package Drupal\openy
 */
interface ContentImporterInterface {

  /**
   * Import content item.
   *
   * @param string $item
   *   Content item name. Example: 'blog'.
   */
  public function import($item);

  /**
   * Return the map of content items and associated migrations.
   *
   * @return array
   *   Map of content items and actual migrations.
   */
  public function getMap();

}
