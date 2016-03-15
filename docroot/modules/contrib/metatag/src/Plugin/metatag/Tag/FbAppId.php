<?php
/**
 * @file
 * Contains \Drupal\metatag\Plugin\metatag\Tag\FbAppId.
 */

namespace Drupal\metatag\Plugin\metatag\Tag;

/**
 * The Facebook "fb:app_id" meta tag.
 *
 * @MetatagTag(
 *   id = "fb_app_id",
 *   label = @Translation("Facebook Application ID"),
 *   description = @Translation("A comma-separated list of Facebook Platform Application IDs applicable for this site."),
 *   name = "fb:app_id",
 *   group = "facebook",
 *   weight = 2,
 *   image = FALSE,
 *   multiple = FALSE
 * )
 */
class FbAppId extends MetaPropertyBase {
  // Nothing here yet. Just a placeholder class for a plugin.
}
