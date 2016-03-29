<?php
/**
 * @file
 * Contains \Drupal\simple_sitemap\Annotation\LinkGenerator.
 */

namespace Drupal\simple_sitemap\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a LinkGenerator item annotation object.
 *
 * @see \Drupal\simple_sitemap\Plugin\SimplesitemapManager
 * @see plugin_api
 *
 * @Annotation
 */
class LinkGenerator extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;
}
