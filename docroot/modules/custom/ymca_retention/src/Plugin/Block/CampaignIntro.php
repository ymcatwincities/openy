<?php

namespace Drupal\ymca_retention\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides an intro block with logo and dates of campaign.
 *
 * @Block(
 *   id = "retention_campaign_intro_block",
 *   admin_label = @Translation("YMCA retention campaign intro block"),
 *   category = @Translation("YMCA Blocks")
 * )
 */
class CampaignIntro extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    // Get page title.
    // @todo Make title editable, create block settings.
    $title = 'Y GAMES 2016';

    // Get retention settings.
    $settings = \Drupal::config('ymca_retention.general_settings');

    // Get start and end date of retention campaign.
    $date_start = new \DateTime($settings->get('date_campaign_open'));
    $date_end = new \DateTime($settings->get('date_campaign_close'));

    /** @var \Drupal\Core\Datetime\DateFormatter $date_formatter */
    $date_formatter = \Drupal::service('date.formatter');

    // Prepare campaign dates.
    $dates = $date_formatter->format($date_start->getTimestamp(), 'custom', 'F j');
    $dates .= ' – ';
    if ($date_start->format('F') == $date_end->format('F')) {
      $dates .= $date_formatter->format($date_end->getTimestamp(), 'custom', 'j');
    }
    else {
      $dates .= $date_formatter->format($date_end->getTimestamp(), 'custom', 'F j');
    }
    return [
      '#theme' => 'ymca_retention_intro',
      '#content' => [
        'title' => $title,
        'dates' => $dates,
      ],
      '#cache' => [
        'contexts' => [
          'url.path',
        ],
      ],
    ];
  }

}
