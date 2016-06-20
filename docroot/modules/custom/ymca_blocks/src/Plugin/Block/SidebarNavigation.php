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
    $builder = \Drupal::service('ymca.menu_builder');
    $active_menu_tree = $builder->getActiveMenuTree();

    // Reduce page tree only for context for Location and Camps.
    if ($site_section = \Drupal::service('pagecontext.service')->getContext()) {
      if ($site_section->bundle() == 'location' || $site_section->bundle() == 'camp') {
        foreach ($active_menu_tree['children'] as $key => $child) {
          if (isset($child['active']) && $child['active']) {
            foreach ($child['children'] as $sub_key => $sub_child) {
              if (isset($sub_child['active']) && $sub_child['active']) {
                $active_menu_tree = $active_menu_tree['children'][$key]['children'][$sub_key];
              }
            }
          }
        }
      }
    }

    $menu_name = '';
    if ($mlid = $builder->getActiveMlid()) {
      $connection = \Drupal::database();
      $query = $connection->select('menu_tree', 'mt')
        ->fields('mt', ['menu_name'])
        ->condition('mt.mlid', $mlid)
        ->execute();
      $menu_name = $query->fetchField();
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
        'contexts' => [
          'url.path'
        ],
      ],
    ];
  }

}
