<?php

namespace Drupal\ymca_membership\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Implements YMCAMembershipPage.
 */
class YMCAMembershipPage extends ControllerBase {

  /**
   * Show the page.
   */
  public function pageView() {

    $assets = \Drupal::config('ymca_membership.assets')->get();
    dpm($assets, '$assets');

    $block = '<div style="border: 10px solid block;">block</div>';

    return [
      '#block_price_top' => $block,
      '#block_price_bottom' => $block,
      '#theme' => 'membership_page',
      '#cache' => [
        'max-age' => 0,
      ],
      '#attached' => [
        'library' => [
          'ymca_membership/membership_page',
        ],
      ],
    ];
  }

}
