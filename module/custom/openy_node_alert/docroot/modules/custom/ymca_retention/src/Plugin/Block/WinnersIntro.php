<?php

namespace Drupal\ymca_retention\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a winners block.
 *
 * @Block(
 *   id = "retention_winners_intro_block",
 *   admin_label = @Translation("[YMCA Retention] Winners intro"),
 *   category = @Translation("YMCA Blocks")
 * )
 */
class WinnersIntro extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $settings = \Drupal::config('ymca_retention.general_settings');
    $date_winners_announcement = new \DateTime($settings->get('date_winners_announcement'));
    /** @var \Drupal\Core\Datetime\DateFormatter $date_formatter */
    $date_formatter = \Drupal::service('date.formatter');
    $description = $this->t('Come back @date to find out if you are a winner!',
      [
        '@date' => $date_formatter->format($date_winners_announcement->getTimestamp(), 'custom', 'F j'),
      ]);

    return [
      '#theme' => 'ymca_retention_winners_intro',
      '#description' => $description,
    ];
  }

}
