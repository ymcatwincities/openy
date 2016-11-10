<?php

namespace Drupal\ymca_retention\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a block with registration form.
 *
 * @Block(
 *   id = "retention_slider",
 *   admin_label = @Translation("[YMCA Retention] Slider"),
 *   category = @Translation("YMCA Blocks")
 * )
 */
class Slider extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    // Get YMCA Retention slider settings.
    $settings = \Drupal::config('ymca_retention.slider');
    $slides = $settings->get('slides');

    return [
      '#theme' => 'ymca_retention_slider',
      '#slides' => $slides,
      '#cache' => [
        'contexts' => [
          'url.path',
        ],
        'max-age' => 0,
      ],
    ];
  }

}
