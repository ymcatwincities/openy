<?php

namespace Drupal\ymca_retention\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a user menu block.
 *
 * @Block(
 *   id = "retention_user_menu_block",
 *   admin_label = @Translation("[YMCA Retention] User menu"),
 *   category = @Translation("YMCA Blocks")
 * )
 */
class UserMenu extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    return [
      '#theme' => 'ymca_retention_user_menu',
      '#content' => [],
    ];
  }

}
