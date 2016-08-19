<?php

namespace Drupal\ymca_retention\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a block with form for login to track activity.
 *
 * @Block(
 *   id = "retention_track_activity_login_block",
 *   admin_label = @Translation("YMCA retention track activity login block"),
 *   category = @Translation("YMCA Blocks")
 * )
 */
class TrackActivityLoginForm extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $form = \Drupal::formBuilder()
      ->getForm('\Drupal\ymca_retention\Form\MemberTrackActivityLoginForm');
    return [
      '#theme' => 'ymca_retention_login_form',
      'form' => $form,
    ];
  }

}
