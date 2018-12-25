<?php

namespace Drupal\file_entity\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Listens to the dynamic route events.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  public function alterRoutes(RouteCollection $collection) {
    // The usage view should be shown in the admin theme.
    if ($route = $collection->get('view.file_entity_files.usage')) {
      $route->setOption('_admin_route', TRUE);
    }
  }
}
