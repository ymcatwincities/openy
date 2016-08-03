<?php

namespace Drupal\ymca_retention\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a personal result block.
 *
 * @Block(
 *   id = "retention_personal_result_block",
 *   admin_label = @Translation("YMCA retention personal result block"),
 *   category = @Translation("YMCA Blocks")
 * )
 */
class PersonalResult extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    return [
      '#theme' => 'ymca_retention_personal_result',
    ];
  }

}
