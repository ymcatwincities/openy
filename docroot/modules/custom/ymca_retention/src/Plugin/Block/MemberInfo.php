<?php

namespace Drupal\ymca_retention\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a block with registration form.
 *
 * @Block(
 *   id = "retention_member_info_block",
 *   admin_label = @Translation("YMCA retention member info block"),
 *   category = @Translation("YMCA Blocks")
 * )
 */
class MemberInfo extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    /** @var \Drupal\user\SharedTempStore $temp_store */
    $temp_store = \Drupal::service('user.shared_tempstore')
      ->get('ymca_retention');
    $member = $temp_store->get('member');

    return [
      '#theme' => 'ymca_retention_member_info',
      '#member' => [
        'name' => 'Carl Philipp Emanuel',
        'goal' => 15,
        'visits' => 5,
        'percentage' => min(round((5 / 15) * 100), 100),
        'activities' => 12,
        'rank' => 123,
      ],
    ];
  }

}
