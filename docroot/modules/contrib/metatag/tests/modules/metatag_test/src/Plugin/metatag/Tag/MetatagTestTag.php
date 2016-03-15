<?php
/**
 * @file
 * Contains \Drupal\metatag_test\Tag\metatag_test\Tag\MetatagTestTag.
 */
namespace Drupal\metatag_test\Plugin\metatag\Tag;

use Drupal\metatag\Plugin\metatag\Tag\MetaNameBase;

/**
 * A metatag tag for testing.
 *
 * @MetatagTag(
 *   id = "metatag_test",
 *   label = @Translation("Metatag Test"),
 *   description = @Translation("A metatag tag for testing."),
 *   name = "metatag_test",
 *   group = "basic",
 *   weight = 3,
 *   image = FALSE,
 *   multiple = FALSE
 * )
 */
class MetatagTestTag extends MetaNameBase {
}
