<?php
/**
 * @file
 * Contains \Drupal\metatag\Plugin\metatag\Tag\OgSiteName.
 */

namespace Drupal\metatag\Plugin\metatag\Tag;

use Drupal\Core\Annotation\Translation;
use Drupal\metatag\Plugin\metatag\Tag\MetaPropertyBase;
use Drupal\metatag\Annotation\MetatagTag;

/**
 * The Open Graph "Site name" meta tag.
 *
 * @MetatagTag(
 *   id = "og_site_name",
 *   label = @Translation("Site name"),
 *   description = @Translation("A human-readable name for the site, e.g., <em>IMDb</em>."),
 *   name = "og:site_name",
 *   group = "open_graph",
 *   weight = 1,
 *   image = FALSE,
 *   multiple = FALSE
 * )
 */
class OgSiteName extends MetaPropertyBase {
  // Nothing here yet. Just a placeholder class for a plugin.
}
