<?php

namespace Drupal\ymca_alters\Routing;

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
        case '/node/{node}':
          $item->setDefault('_controller', 'Drupal\ymca_alters\Controller\NodeViewController::view');
          break;

        case '/sitemap':
          $item->setPath('/sitemap_navigation');
          break;
      }
    }
  }

}
