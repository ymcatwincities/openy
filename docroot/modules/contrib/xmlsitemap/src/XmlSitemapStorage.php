<?php

/**
 * @file
 * Definition of Drupal\xmlsitemap\XmlSitemapStorage.
 */

namespace Drupal\xmlsitemap;

use Drupal\Core\Config\Entity\ConfigEntityStorage;

/**
 * Defines a handler class for xmlsitemap entities.
 */
class XmlSitemapStorage extends ConfigEntityStorage implements XmlSitemapStorageInterface {

  /**
   * {@inheritdoc}
   */
  public function loadByContext(array $context = NULL) {
    if (!isset($context)) {
      $context = xmlsitemap_get_current_context();
    }
    $sitemaps = $this->loadMultiple();
    foreach ($sitemaps as $sitemap) {
      if ($sitemap->context == $context) {
        return $sitemap;
      }
    }
    return NULL;
  }

}
