<?php
/**
 * @file
 * Contains \Drupal\metatag\Plugin\metatag\Group\OpenGraph.
 */

namespace Drupal\metatag\Plugin\metatag\Group;


/**
 * The open graph group.
 *
 * @MetatagGroup(
 *   id = "open_graph",
 *   label = @Translation("Open Graph"),
 *   description = @Translation("The <a href='http://ogp.me/'>Open Graph meta tags</a> are used control how Facebook, Pinterest, LinkedIn and other social networking sites interpret the site's content."),
 *   weight = 3
 * )
 */
class OpenGraph extends GroupBase {
  // Inherits everything from Base.
}
