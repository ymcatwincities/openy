<?php

namespace Drupal\ymca_aliases\EventSubscriber;

use Drupal\Core\Routing\RouteProvider;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\RouteCollection;
use Drupal\Component\Utility\Unicode;

/**
 * KernelEvents::REQUEST subscriber for redirecting q=path/to/page requests.
 */
class LowercaseSubscriber implements EventSubscriberInterface {

  /**
   * Route provider service.
   *
   * @var RouteProvider
   */
  protected $routeProvider;

  /**
   * LowercaseSubscriber constructor.
   *
   * @param \Drupal\Core\Routing\RouteProvider $routeProvider
   *   Passed router provider.
   */
  public function __construct(RouteProvider $routeProvider) {
    $this->routeProvider = $routeProvider;
  }

  /**
   * Normalizes the path aliases.
   *
   * @param \Symfony\Component\HttpKernel\Event\GetResponseEvent $event
   *   Passed event.
   */
  public function redirectLowercaseAliases(GetResponseEvent $event) {
    $request = $event->getRequest();
    $path = $request->getPathInfo();
    /** @var RouteCollection $ret */
    $ret = $this->routeProvider->getRoutesByPattern($path);
    if ($ret->count() == NULL) {
      return;
    }
    if (Unicode::strtolower($path) !== $path && $request->server->get('HTTP_X_REQUESTED_WITH') !== 'XMLHttpRequest') {
      $event->setResponse(new RedirectResponse(Unicode::strtolower($GLOBALS['base_url'] . $path), 301));
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    // This needs to run before RouterListener::onKernelRequest(), which has
    // a priority of 32. Otherwise, that aborts the request if no matching
    // route is found.

    $events[KernelEvents::REQUEST][] = array('redirectLowercaseAliases', 36);

    return $events;
  }

}
