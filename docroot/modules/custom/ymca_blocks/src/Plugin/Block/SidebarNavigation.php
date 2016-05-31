<?php

namespace Drupal\ymca_blocks\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\ymca_menu\YMCAMenuBuilder;

/**
 * Provides Sidebar Navigation block.
 *
 * @Block(
 *   id = "sidebar_navigation_block",
 *   admin_label = @Translation("Sidebar Navigation"),
 *   category = @Translation("YMCA Blocks")
 * )
 */
class SidebarNavigation extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $builder = new YMCAMenuBuilder();
    $active_menu_tree = $builder->getActiveMenuTree();

    if ($link = \Drupal::service('menu.active_trail')->getActiveLink()) {
      $menu_name = $link->getMenuName();
    }

    return [
      '#theme' => 'sidebar_navigation_block',
      '#content' => array(
        'active_menu_tree' => $active_menu_tree,
      ),
      '#contextual_links' => [
        'menu' => [
          'route_parameters' => ['menu' => $menu_name],
        ],
      ],
      '#attributes' => ['class' => ['panel', 'panel-default', 'panel-subnav']],
      '#cache' => [
        'max-age' => 0,
      ],
    ];
  }

}
