<?php
/**
 * @file
 * Contains \Drupal\metatag\Plugin\metatag\Tag\Generator.
 */

namespace Drupal\metatag\Plugin\metatag\Tag;

use Drupal\Core\Annotation\Translation;
use Drupal\metatag\Plugin\metatag\Tag\MetaNameBase;
use Drupal\metatag\Annotation\MetatagTag;

/**
 * The basic "Generator" meta tag.
 *
 * @MetatagTag(
 *   id = "generator",
 *   label = @Translation("Generator"),
 *   description = @Translation("Describes the name and version number of the software or publishing tool used to create the page."),
 *   name = "generator",
 *   group = "advanced",
 *   weight = 4,
 *   image = FALSE,
 *   multiple = FALSE
 * )
 */
class Generator extends LinkRelBase {
  // Nothing here yet. Just a placeholder class for a plugin.
}
