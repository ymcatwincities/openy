<?php
/**
 * @file
 * Contains \Drupal\metatag\Plugin\metatag\Tag\OgType.
 */

namespace Drupal\metatag\Plugin\metatag\Tag;

use Drupal\Core\Annotation\Translation;
use Drupal\metatag\Plugin\metatag\Tag\MetaPropertyBase;
use Drupal\metatag\Annotation\MetatagTag;

/**
 * The Open Graph "Type" meta tag.
 *
 * @MetatagTag(
 *   id = "og_type",
 *   label = @Translation("Content type"),
 *   description = @Translation("The type of the content, e.g., <em>movie</em>."),
 *   name = "og:type",
 *   group = "open_graph",
 *   weight = 2,
 *   image = FALSE,
 *   multiple = FALSE
 * )
 */
class OgType extends MetaPropertyBase {
  // Nothing here yet. Just a placeholder class for a plugin.
}
