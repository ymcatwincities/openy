<?php

namespace Drupal\ymca_page_manager\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Listens to the dynamic route events.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  public function alterRoutes(RouteCollection $collection) {
    foreach ($collection as &$item) {
      /** @var Route $item */
      switch ($item->getPath()) {
        case '/admin/structure/page_manager':
          $item->setRequirement('_permission', 'view page_manager pages list');
          break;
      }
    }
  }

}
