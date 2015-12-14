<?php

/**
 * @file
 * Contains \Drupal\xmlsitemap\XmlSitemapStorageInterface.
 */

namespace Drupal\xmlsitemap;

use Drupal\Core\Config\Entity\ConfigEntityStorageInterface;

/**
 * Defines a common interface for xmlsitemap entity handler classes.
 */
interface XmlSitemapStorageInterface extends ConfigEntityStorageInterface {

  /**
   * Returns the sitemap with the context specified as parameter.
   *
   * @param array $context
   *   An optional XML sitemap context array to use to find the correct XML
   *   sitemap. If not provided, the current site's context will be used.
   *
   * @return Drupal\xmlsitemap\XmlSitemapInterface
   *   Sitemap with the specified context or NULL.
   */
  public function loadByContext(array $context = NULL);
}
