<?php

namespace Drupal\ymca_retention\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a tabs selector block.
 *
 * @Block(
 *   id = "retention_tabs_selector_block",
 *   admin_label = @Translation("[YMCA Retention] Tabs selector"),
 *   category = @Translation("YMCA Blocks")
 * )
 */
class TabsSelector extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    return [
      '#theme' => 'ymca_retention_tabs_selector',
      '#content' => [],
      '#attached' => [
        'library' => [
          'ymca_retention/tabs-selector',
        ],
      ],
    ];
  }

}
