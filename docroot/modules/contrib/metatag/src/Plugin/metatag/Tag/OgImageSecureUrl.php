<?php
/**
 * @file
 * Contains Drupal\metatag\Plugin\metatag\Tag\OgImageSecureUrl.
 */

namespace Drupal\metatag\Plugin\metatag\Tag;

use Drupal\Core\Annotation\Translation;
use Drupal\metatag\Plugin\metatag\Tag\MetaPropertyBase;
use Drupal\metatag\Annotation\MetatagTag;

/**
 * Provides a plugin for the 'og:image:secure_url' meta tag.
 *
 * @MetatagTag(
 *   id = "og_image_secure_url",
 *   label = @Translation("Image Secure URL"),
 *   description = @Translation("The secure URL (HTTPS) of an image which should represent the content. The image must be at least 50px by 50px and have a maximum aspect ratio of 3:1. Supports PNG, JPEG and GIF formats. All 'http://' URLs will automatically be converted to 'https://'."),
 *   name = "og:image:secure_url",
 *   group = "open_graph",
 *   weight = 11,
 *   image = FALSE,
 *   multiple = FALSE
 * )
 */
class OgImageSecureUrl extends MetaPropertyBase {
  // Nothing here yet. Just a placeholder class for a plugin.
}
