<?php

namespace Drupal\openy_campaign\Theme;

use Drupal\Core\Theme\ThemeNegotiatorInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\node\NodeInterface;

class ThemeNegotiator implements ThemeNegotiatorInterface {

  /**
   * {@inheritdoc}
   */
  public function applies(RouteMatchInterface $route_match) {
    $customRoutes = [
      'openy_campaign.campaign_game',
      'openy_campaign.team_member.list',
      'openy_campaign.team_member.edit_form',
      'openy_campaign.member-registration-portal',
    ];

    $possible_routes = $customRoutes + ['entity.node.canonical'];

    if (in_array($route_match->getRouteName(), $possible_routes)) {
      $node = $route_match->getParameter('node');

      if (
        in_array($route_match->getRouteName(), $customRoutes) ||
        ($node instanceof NodeInterface === TRUE && in_array($node->getType(), ['campaign', 'campaign_page']))
      ) {
        return TRUE;
      }

      // Check if requested node uses in campaign.
      $query = \Drupal::entityQuery('node')
        ->condition('status', 1)
        ->condition('type', 'campaign');
      $orGroup = $query->orConditionGroup()
        ->condition('field_campaign_pages', $node->id(), 'IN')
        ->condition('field_pause_landing_page', $node->id());

      $campaignLandingPages = $query->condition($orGroup)->execute();
      if (!empty($campaignLandingPages)) {
        return TRUE;
      }

    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function determineActiveTheme(RouteMatchInterface $route_match) {
    // Here return the actual theme name.
    return CAMPAIGN_THEME;
  }
}
