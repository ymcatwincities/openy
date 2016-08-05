<?php

namespace Drupal\ymca_retention\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\ymca_retention\Entity\Winner;

/**
 * Provides a personal result block.
 *
 * @Block(
 *   id = "retention_personal_result_block",
 *   admin_label = @Translation("YMCA retention personal result block"),
 *   category = @Translation("YMCA Blocks")
 * )
 */
class PersonalResult extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $email = strtolower(\Drupal::request()->query->get('email'));
    if (!$email) {
      return [];
    }
    $theme = 'ymca_retention_personal_result_none';

    $member_ids = \Drupal::entityQuery('ymca_retention_member')
      ->condition('mail', $email)
      ->execute();
    if (empty($member_ids)) {
      return [
        '#theme' => $theme,
      ];
    }
    $member_id = reset($member_ids);
    $winner_ids = \Drupal::entityQuery('ymca_retention_winner')
      ->condition('member', $member_id)
      ->execute();
    if (empty($winner_ids)) {
      return [
        '#theme' => $theme,
      ];
    }
    $winner_id = reset($winner_ids);
    /** @var Winner $winner */
    $winner = Winner::load($winner_id);

    switch ($winner->get('place')->value) {
      case 1:
        $theme = 'ymca_retention_personal_result_gold';
        break;

      case 2:
        $theme = 'ymca_retention_personal_result_silver';
        break;

      case 3:
        $theme = 'ymca_retention_personal_result_bronze';
        break;
    }
    switch ($winner->get('track')->value) {
      case 'swimming':
        $track = $this->t('swimming');
        break;

      case 'fitness':
        $track = $this->t('fitness');
        break;

      case 'groupex':
        $track = $this->t('group exercises');
        break;

      default:
        $track = $this->t('visit goal');
        break;
    }

    return [
      '#theme' => $theme,
      '#track' => $track,
    ];
  }

}
