<?php

namespace Drupal\ymca_retention\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a games ended block.
 *
 * @Block(
 *   id = "retention_games_ended_block",
 *   admin_label = @Translation("YMCA retention games ended block"),
 *   category = @Translation("YMCA Blocks")
 * )
 */
class GamesEnded extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    return [
      '#theme' => 'ymca_retention_games_ended',
    ];
  }

}
