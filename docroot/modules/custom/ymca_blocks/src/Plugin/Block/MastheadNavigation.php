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
    $builder = \Drupal::service('ymca.menu_builder');
    $active_menu_tree = $builder->getActiveMenuTree();

    return [
      '#theme' => 'masthead_navigation_block',
      '#content' => array(
        'active_menu_tree' => $active_menu_tree,
      ),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    $cache_tags = parent::getCacheTags();
    // Include all menus cache tags.
    $menus = \Drupal::config('ymca_menu.menu_list')->get('menu_list');
    foreach ($menus as $menu) {
      $cache_tags[] = 'config:system.menu.' . $menu;
    }
    return $cache_tags;
  }

}
