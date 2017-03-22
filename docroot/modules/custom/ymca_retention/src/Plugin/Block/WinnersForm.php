<?php

namespace Drupal\ymca_retention\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a block with form for winners branches.
 *
 * @Block(
 *   id = "retention_winners_form_block",
 *   admin_label = @Translation("[YMCA Retention] Winners Form"),
 *   category = @Translation("YMCA Blocks")
 * )
 */
class WinnersForm extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    return [
      '#theme' => 'ymca_retention_winners_form',
    ];
  }

}
