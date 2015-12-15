<?php
/**
 * @file
 * Contains \Drupal\metatag\Plugin\metatag\Tag\OgTitle.
 */

namespace Drupal\metatag\Plugin\metatag\Tag;

use Drupal\Core\Annotation\Translation;
use Drupal\metatag\Plugin\metatag\Tag\MetaPropertyBase;
use Drupal\metatag\Annotation\MetatagTag;

/**
 * The Open Graph "Title" meta tag.
 *
 * @MetatagTag(
 *   id = "og_title",
 *   label = @Translation("Title"),
 *   description = @Translation("The title of the content, e.g., <em>The Rock</em>."),
 *   name = "og:title",
 *   group = "open_graph",
 *   weight = 4,
 *   image = FALSE,
 *   multiple = FALSE
 * )
 */
class OgTitle extends MetaPropertyBase {
  // Nothing here yet. Just a placeholder class for a plugin.
}
