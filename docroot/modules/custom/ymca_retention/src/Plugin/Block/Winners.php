<?php

namespace Drupal\ymca_retention\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a winners block.
 *
 * @Block(
 *   id = "retention_winners_block",
 *   admin_label = @Translation("YMCA retention winners block"),
 *   category = @Translation("YMCA Blocks")
 * )
 */
class Winners extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    return [
      '#theme' => 'ymca_retention_winners',
    ];
  }

}
