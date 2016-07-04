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
    $request = \Drupal::request();
    $route_match = \Drupal::routeMatch();
    $title = \Drupal::service('title_resolver')
      ->getTitle($request, $route_match->getRouteObject());
    $dates = 'July 25 - August 16';

    return [
      '#theme' => 'ymca_retention_intro',
      '#content' => array(
        'title' => $title,
        'dates' => $dates,
      ),
      '#cache' => [
        'contexts' => [
          'url.path',
        ],
      ],
    ];
  }

}
