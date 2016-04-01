<?php

namespace Drupal\ymca_menu;

use Drupal\menu_ui\MenuListBuilder as CoreMenuListBuilder;

/**
 * Overrides core menu list builder.
 *
 * Hides main menu from menu list.
 *
 * @see \Drupal\system\Entity\Menu
 * @see menu_entity_info()
 */
class MenuListBuilder extends CoreMenuListBuilder {

  /**
   * {@inheritdoc}
   */
  public function render() {
    $build = parent::render();
    $build['table']['#rows']['main'] = [
      'data' => $build['table']['#rows']['main'],
      'class' => 'hidden',
    ];
    return $build;
  }

}
