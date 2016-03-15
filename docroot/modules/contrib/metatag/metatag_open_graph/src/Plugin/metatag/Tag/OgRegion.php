<?php
/**
 * @file
 * Contains \Drupal\metatag_open_graph\Plugin\metatag\Tag\OgRegion.
 */

namespace Drupal\metatag_open_graph\Plugin\metatag\Tag;

use \Drupal\metatag\Plugin\metatag\Tag\MetaPropertyBase;

/**
 * Provides a plugin for the 'og:region' meta tag.
 *
 * @MetatagTag(
 *   id = "og_region",
 *   label = @Translation("Region"),
 *   description = @Translation(""),
 *   name = "og:region",
 *   group = "open_graph",
 *   weight = 20,
 *   image = FALSE,
 *   multiple = FALSE
 * )
 */
class OgRegion extends MetaPropertyBase {
  // Nothing here yet. Just a placeholder class for a plugin.
}
