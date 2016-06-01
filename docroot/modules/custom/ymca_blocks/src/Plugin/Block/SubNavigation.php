<?php

namespace Drupal\ymca_blocks\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\ymca_menu\YMCAMenuBuilder;

/**
 * Provides Sub-Navigation block.
 *
 * @Block(
 *   id = "sub_navigation_block",
 *   admin_label = @Translation("Sub-navigation block"),
 *   category = @Translation("YMCA Blocks")
 * )
 */
class SubNavigation extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $builder = new YMCAMenuBuilder();
    $active_menu_tree = $builder->getActiveMenuTree();

    return [
      '#theme' => 'sub_navigation_block',
      '#content' => array(
        'active_menu_tree' => $active_menu_tree,
      ),
      '#cache' => [
        'max-age' => 0,
      ],
    ];
  }

}
