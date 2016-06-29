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
    $builder = \Drupal::service('ymca.menu_builder');
    $active_menu_tree = $builder->getActiveMenuTree();

    $menu_name = '';
    if ($mlid = $builder->getActiveMlid()) {
      if ($link = \Drupal::entityTypeManager()->getStorage('menu_link_content')->load($mlid)) {
        $menu_name = $link->getMenuName();
      }
    }

    return [
      '#theme' => 'sub_navigation_block',
      '#content' => array(
        'active_menu_tree' => $active_menu_tree,
      ),
      '#contextual_links' => [
        'menu' => [
          'route_parameters' => ['menu' => $menu_name],
        ],
      ],
      '#cache' => [
        'contexts' => [
          'url.path'
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    $cache_tags = parent::getCacheTags();

    // For sub-navigation block we simply define corresponding menu tags.
    if ($context = \Drupal::service('pagecontext.service')->getContext()) {
      switch ($context->bundle()) {
        case 'camp':
          $cache_tags[] = 'config:system.menu.camps';
          break;

        case 'location':
          $cache_tags[] = 'config:system.menu.locations';
          break;

      }
    }

    return $cache_tags;
  }

}
