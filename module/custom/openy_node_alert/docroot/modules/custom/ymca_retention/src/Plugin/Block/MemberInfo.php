<?php

namespace Drupal\ymca_retention\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\ymca_retention\AnonymousCookieStorage;
use Drupal\ymca_retention\Entity\Member;

/**
 * Provides a block with registration form.
 *
 * @Block(
 *   id = "retention_member_info_block",
 *   admin_label = @Translation("[YMCA Retention] Member info"),
 *   category = @Translation("YMCA Blocks")
 * )
 */
class MemberInfo extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $member_id = AnonymousCookieStorage::get('ymca_retention_member');
    if (empty($member_id)) {
      return NULL;
    }
    /** @var Member $member */
    $member = Member::load($member_id);
    if (empty($member)) {
      return NULL;
    }
    $goal = $member->getVisitGoal();
    $visits = $member->getVisits();

    $rank = $member->getMemberRank();

    return [
      '#theme' => 'ymca_retention_member_info',
      '#member' => [
        'name' => $member->getFullName(),
        'goal' => $goal,
        'visits' => $visits,
        'percentage' => min(round(($visits / $goal) * 100), 100),
        'rank' => $rank,
      ],
    ];
  }

}
