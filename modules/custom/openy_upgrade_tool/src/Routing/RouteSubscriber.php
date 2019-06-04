<?php

namespace Drupal\openy_upgrade_tool\Routing;

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
    if ($route = $collection->get('entity.openy_upgrade_log.collection')) {
      $route->setDefaults([
        '_controller' => '\Drupal\openy_upgrade_tool\Controller\OpenyUpgradeLogController::dashboard',
        '_title' => 'Upgrade Tool Dashboard',
      ]);
    }
  }

}
