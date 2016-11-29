<?php

namespace Drupal\ymca_retention\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Url;
use Drupal\ymca_retention\AnonymousCookieStorage;
use Drupal\ymca_retention\Entity\Member;

/**
 * Provides a block with a message about successful registration.
 *
 * @Block(
 *   id = "retention_registration_confirmation_block",
 *   admin_label = @Translation("[YMCA Retention] Registration confirmation"),
 *   category = @Translation("YMCA Blocks")
 * )
 */
class RegistrationConfirmation extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $member_id = AnonymousCookieStorage::get('ymca_retention_member');
    if (empty($member_id)) {
      return NULL;
    }
    $member = Member::load($member_id);
    if (empty($member)) {
      return NULL;
    }

    return [
      '#theme' => 'ymca_retention_registration_confirmation',
      '#member' => [
        'name' => $member->getFirstName(),
        'goal' => $member->getVisitGoal(),
        'activity_url' => Url::fromRoute('page_manager.page_view_ymca_retention_pages', [
          'string' => $member->isCreatedByStaff() ? 'team' : 'activity',
        ]),
      ],
    ];
  }

}
