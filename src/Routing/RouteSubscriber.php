<?php

namespace Drupal\openy\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Listens to the dynamic route events.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {

    // Replace system.admin_structure '/admin/structure' permission.
    // Note that the second parameter of setRequirement() is a string.
    if ($route = $collection->get('system.admin_structure')) {
      $requirements = $route->getRequirements();
      unset($requirements['_permission']);
      $requirements['_permission'] = 'access structure page';
      $route->setRequirements($requirements);
    }
  }

}
