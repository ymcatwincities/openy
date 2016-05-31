<?php

namespace Drupal\ymca_blocks\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\ymca_menu\YMCAMenuBuilder;

/**
 * Provides Masthead Navigation block.
 *
 * @Block(
 *   id = "masthead_navigation_block",
 *   admin_label = @Translation("Masthead navigation block"),
 *   category = @Translation("YMCA Blocks")
 * )
 */
class MastheadNavigation extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $builder = new YMCAMenuBuilder();
    $active_menu_tree = $builder->getActiveMenuTree();

    return [
      '#theme' => 'masthead_navigation_block',
      '#content' => array(
        'active_menu_tree' => $active_menu_tree,
      ),
      '#cache' => [
        'max-age' => 0,
      ],
    ];
  }

}
