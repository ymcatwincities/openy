<?php

namespace Drupal\ymca_retention\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a block with registration form.
 *
 * @Block(
 *   id = "retention_slider",
 *   admin_label = @Translation("YMCA retention slider block"),
 *   category = @Translation("YMCA Blocks")
 * )
 */
class Slider extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    return [
      '#theme' => 'ymca_retention_slider',
      '#cache' => [
        'contexts' => [
          'url.path',
        ],
        'max-age' => 0,
      ],
    ];
  }

}
