<?php
/**
 * @file
 * Contains \Drupal\metatag\Plugin\metatag\Tag\Keywords.
 */

namespace Drupal\metatag\Plugin\metatag\Tag;

/**
 * The basic "Keywords" meta tag.
 *
 * @MetatagTag(
 *   id = "keywords",
 *   label = @Translation("Keywords"),
 *   description = @Translation("A comma-separated list of keywords about the page. This meta tag is <em>not</em> supported by most search engines anymore."),
 *   name = "keywords",
 *   group = "basic",
 *   weight = 4,
 *   image = FALSE,
 *   multiple = FALSE
 * )
 */
class Keywords extends MetaNameBase {
  // Nothing here yet. Just a placeholder class for a plugin.
}
