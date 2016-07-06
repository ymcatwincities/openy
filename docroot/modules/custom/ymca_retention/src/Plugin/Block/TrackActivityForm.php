<?php

namespace Drupal\ymca_retention\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a block with form for tracking activity.
 *
 * @Block(
 *   id = "retention_track_activity_block",
 *   admin_label = @Translation("YMCA retention track activity block"),
 *   category = @Translation("YMCA Blocks")
 * )
 */
class TrackActivityForm extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $form = \Drupal::formBuilder()
      ->getForm('\Drupal\ymca_retention\Form\MemberTrackActivityForm');
    return [
      '#theme' => 'ymca_retention_track_activity_form',
      'form' => $form,
    ];
  }

}
