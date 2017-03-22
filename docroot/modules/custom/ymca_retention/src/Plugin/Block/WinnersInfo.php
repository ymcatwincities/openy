<?php

namespace Drupal\ymca_retention\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a block with winners info.
 *
 * @Block(
 *   id = "retention_winners_info_block",
 *   admin_label = @Translation("[YMCA Retention] Winners Info"),
 *   category = @Translation("YMCA Blocks")
 * )
 */
class WinnersInfo extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    return [
      '#theme' => 'ymca_retention_winners_info',
    ];
  }

}
