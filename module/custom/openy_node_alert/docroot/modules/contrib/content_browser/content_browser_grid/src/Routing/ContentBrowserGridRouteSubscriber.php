<?php

/**
 * @file
 * Contains \Drupal\content_browser_grid\Routing\RouteSubscriber.
 */

namespace Drupal\content_browser_grid\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Use the frontend theme conditionally for our entity browser.
 */
class ContentBrowserGridRouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  public function alterRoutes(RouteCollection $collection) {
    if ($route = $collection->get('entity_browser.browse_content_grid')) {
      $route->setOption('_admin_route', FALSE);
    }
  }

}
