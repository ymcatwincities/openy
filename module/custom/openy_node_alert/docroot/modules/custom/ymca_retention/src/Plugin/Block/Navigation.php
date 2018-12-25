<?php

namespace Drupal\ymca_retention\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Url;

/**
 * Provides a block with navigation menu on the page.
 *
 * @Block(
 *   id = "retention_navigation_block",
 *   admin_label = @Translation("[YMCA Retention] Navigation"),
 *   category = @Translation("YMCA Blocks")
 * )
 */
class Navigation extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {

    // Build URL to the front page.
    $back_url = Url::fromRoute('<front>', [], ['absolute' => TRUE])->toString();

    return [
      '#theme' => 'ymca_retention_navigation',
      '#content' => [
        'back_url' => $back_url,
      ],
      '#cache' => [
        'contexts' => [
          'url.path',
        ],
        'max-age' => 0,
      ],
    ];
  }

}
