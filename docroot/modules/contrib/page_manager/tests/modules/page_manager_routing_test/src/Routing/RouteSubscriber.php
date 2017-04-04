<?php

namespace Drupal\page_manager_routing_test\Routing;

use Drupal\Core\Routing\RouteBuildEvent;
use Drupal\Core\Routing\RoutingEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Routing\Route;

/**
 * Route subscriber for Page Manager Routing Test.
 */
class RouteSubscriber implements EventSubscriberInterface {

  /**
   * Alters the existing route collection.
   *
   * @param \Drupal\Core\Routing\RouteBuildEvent $event
   *   The route build event.
   */
  public function beforePageManagerRoutes(RouteBuildEvent $event) {
    $collection = $event->getRouteCollection();
    $route = new Route('/entity_test/{entity_test}', [], ['_access' => 'TRUE']);
    $route->setRequirement('_format', 'xml');
    $collection->add('entity.entity_test.canonical.xml', $route);
  }

  /**
   * Alters the existing route collection.
   *
   * @param \Drupal\Core\Routing\RouteBuildEvent $event
   *   The route build event.
   */
  public function afterPageManagerRoutes(RouteBuildEvent $event) {
    $collection = $event->getRouteCollection();
    if ($original_route = $collection->get('entity.entity_test.canonical')) {
      $route = new Route($original_route->getPath(), $original_route->getDefaults(), $original_route->getRequirements(), $original_route->getOptions());
      $route->setRequirement('_format', 'json');
      $collection->add('entity.entity_test.canonical.json', $route);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    // Run before PageManagerRoutes.
    $events[RoutingEvents::ALTER][] = ['beforePageManagerRoutes', -155];
    // Run after PageManagerRoutes.
    $events[RoutingEvents::ALTER][] = ['afterPageManagerRoutes', -165];
    return $events;
  }

}
