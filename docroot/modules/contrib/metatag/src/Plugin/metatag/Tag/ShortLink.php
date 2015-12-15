<?php
/**
 * @file
 * Contains \Drupal\metatag\Plugin\metatag\Tag\ShortLink.
 */

namespace Drupal\metatag\Plugin\metatag\Tag;

use Drupal\Core\Annotation\Translation;
use Drupal\metatag\Plugin\metatag\Tag\LinkRelBase;
use Drupal\metatag\Annotation\MetatagTag;

/**
 * Provides a plugin for the 'shortlink' meta tag.
 *
 * @MetatagTag(
 *   id = "shortlink",
 *   label = @Translation("Shortlink URL"),
 *   description = @Translation("A brief URL, often created by a URL shortening service."),
 *   name = "shortlink",
 *   group = "advanced",
 *   weight = 1,
 *   image = FALSE,
 *   multiple = FALSE
 * )
 */
class ShortLink extends LinkRelBase {
  // Nothing here yet. Just a placeholder class for a plugin.
}
