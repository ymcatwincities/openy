<?php

namespace Drupal\ymca_retention\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a tabs block.
 *
 * @Block(
 *   id = "retention_tabs_block",
 *   admin_label = @Translation("[YMCA Retention] Tabs"),
 *   category = @Translation("YMCA Blocks")
 * )
 */
class Tabs extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    return [
      '#theme' => 'ymca_retention_tabs',
      '#content' => [],
    ];
  }

}
