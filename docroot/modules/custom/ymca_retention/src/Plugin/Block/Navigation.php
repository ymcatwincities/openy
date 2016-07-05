<?php

namespace Drupal\ymca_retention\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * Provides a block with navigation menu on the page..
 *
 * @Block(
 *   id = "retention_navigation_block",
 *   admin_label = @Translation("YMCA retention navigation block"),
 *   category = @Translation("YMCA Blocks")
 * )
 */
class Navigation extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $links = [
      'registration' => Link::fromTextAndUrl(t('Registration'), Url::fromRoute('<current>', [], ['fragment' => 'register-and-report'])),
      'track_my_activity' => Link::fromTextAndUrl(t('Track My Activities'), Url::fromRoute('<current>', [], ['fragment' => 'register-and-report'])),
      'leader_board' => Link::fromTextAndUrl(t('Leaderboard'), Url::fromRoute('<current>', [], ['fragment' => 'leaderboard'])),
      'rules' => Link::fromTextAndUrl(t('Prizes/Rules Details'), Url::fromRoute('<current>', [], ['fragment' => 'rules']))
        ->toString(),
    ];
    /** @var \Drupal\user\SharedTempStore $temp_store */
    $temp_store = \Drupal::service('user.shared_tempstore')
      ->get('ymca_retention');
    $member = $temp_store->get('member');

    if (!empty($member)) {
      unset($links['registration']);
    }

    return [
      '#theme' => 'ymca_retention_navigation',
      '#content' => [
        'back_url' => Url::fromRoute('<front>', [], ['absolute' => TRUE])
          ->toString(),
        'links' => $links,
      ],
    ];
  }

}
