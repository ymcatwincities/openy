<?php

namespace Drupal\ymca_retention\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\ymca_retention\AnonymousCookieStorage;

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
   * Get list of links for navigation menu.
   *
   * @return array
   *   List of links.
   */
  public function getNavigationLinks() {
    $links = [
      'registration' => Link::fromTextAndUrl(t('Registration'), Url::fromRoute('page_manager.page_view_ymca_retention_campaign', [], [
        'fragment' => 'registration',
      ])),
      'track_my_activity' => Link::fromTextAndUrl(t('Track My Activities'), Url::fromRoute('page_manager.page_view_ymca_retention_campaign', [], [
        'fragment' => 'report',
      ])),
      'leader_board' => Link::fromTextAndUrl(t('Leaderboard'), Url::fromRoute('page_manager.page_view_ymca_retention_campaign', [], [
        'fragment' => 'leaderboard',
      ])),
      'rules' => Link::fromTextAndUrl(t('Prizes & Rules'), Url::fromRoute('page_manager.page_view_ymca_retention_pages', [
        'string' => 'rules',
      ]))->toString(),
    ];
    return $links;
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $links = $this->getNavigationLinks();

    $current_route = $route = \Drupal::service('current_route_match')
      ->getRouteName();

    // Build URL to the front page.
    $back_url = Url::fromRoute('<front>', [], ['absolute' => TRUE])->toString();

    // Display Registration link only on landing page and when member is not identified.
    if ($current_route != 'page_manager.page_view_ymca_retention_campaign') {
      $member_id = AnonymousCookieStorage::get('ymca_retention_member');
      if (!empty($member_id)) {
        unset($links['registration']);
      }
    }
    switch ($current_route) {
      case 'page_manager.page_view_ymca_retention_pages_y_games_enroll_success':
        $links['track_my_activity']->setUrl(Url::fromRoute('page_manager.page_view_ymca_retention_pages', [
          'string' => 'activity',
        ]));
        break;

      case 'page_manager.page_view_ymca_retention_pages_y_games_activity':
        $links['track_my_activity']->setUrl(Url::fromRoute('page_manager.page_view_ymca_retention_pages', [
          'string' => 'activity',
        ], [
          'fragment' => 'track-my-activities',
        ]));
        $links['leader_board']->setUrl(Url::fromRoute('page_manager.page_view_ymca_retention_pages_y_games_activity', [
          'string' => 'activity',
        ], [
          'fragment' => 'leaderboard',
        ]));
        break;
    }

    return [
      '#theme' => 'ymca_retention_navigation',
      '#content' => [
        'back_url' => $back_url,
        'links' => $links,
      ],
      '#cache' => [
        'contexts' => [
          'url.path',
        ],
        'max-age' => 0,
      ],
    ];
  }

}
