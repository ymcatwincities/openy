<?php

/**
 * @file
 * Contains \Drupal\page_manager\EventSubscriber\RouteNameResponseSubscriber.
 */

namespace Drupal\page_manager\EventSubscriber;

use Drupal\Core\Cache\CacheableResponseInterface;
use Drupal\Core\Routing\StackedRouteMatchInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Adds the route name as a cache tag to all cacheable responses.
 */
class RouteNameResponseSubscriber implements EventSubscriberInterface {

  /**
   * The master route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * Constructs a new RouteNameResponseSubscriber.
   *
   * @param \Drupal\Core\Routing\StackedRouteMatchInterface $route_match
   *   The current route match.
   */
  public function __construct(StackedRouteMatchInterface $route_match) {
    $this->routeMatch = $route_match->getMasterRouteMatch();
  }

  /**
   * Adds the route name as a cache tag to all cacheable responses.
   *
   * @param \Symfony\Component\HttpKernel\Event\FilterResponseEvent $event
   *   The event to process.
   */
  public function onResponse(FilterResponseEvent $event) {
    $response = $event->getResponse();
    if ($response instanceof CacheableResponseInterface) {
      $cacheability_metadata = $response->getCacheableMetadata();
      // If the route specifies a 'base route name', use that. Otherwise fall
      // back to the route name. The 'base route name' is specified in
      // \Drupal\page_manager\Routing\PageManagerRoutes.
      $route_name = $this->routeMatch->getParameter('base_route_name') ?: $this->routeMatch->getRouteName();
      $cacheability_metadata->addCacheTags(['page_manager_route_name:' . $route_name]);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    // Run before dynamic_page_cache_subscriber:onResponse.
    $events[KernelEvents::RESPONSE][] = ['onResponse', 101];
    return $events;
  }

}
