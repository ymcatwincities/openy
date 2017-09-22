<?php

namespace Drupal\openy_campaign\Theme;

use Drupal\Core\Theme\ThemeNegotiatorInterface;
use Drupal\Core\Routing\RouteMatchInterface;

class ThemeNegotiator implements ThemeNegotiatorInterface {

  /**
   * {@inheritdoc}
   */
  public function applies(RouteMatchInterface $route_match) {

    $possible_routes = array(
      'entity.node.canonical',
    );

    if (in_array($route_match->getRouteName(), $possible_routes)) {
      $node = $route_match->getParameter('node');
      return ($node->getType() === 'campaign');
    }

    return false;
  }

  /**
   * {@inheritdoc}
   */
  public function determineActiveTheme(RouteMatchInterface $route_match) {
    // Here return the actual theme name.
    return 'openy_campaign_theme';
  }
}