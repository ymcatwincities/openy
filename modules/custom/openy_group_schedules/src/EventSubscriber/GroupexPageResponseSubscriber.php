<?php

namespace Drupal\openy_group_schedules\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * KernelEvents::REQUEST subscriber for starting session for anonymous users.
 */
class GroupexPageResponseSubscriber implements EventSubscriberInterface {

  /**
   * Disable Varnish and Page Cache.
   *
   * We are dealing with Dynamic Page Cache and BigPipe plus lazy loaders.
   *
   * @param @param \Symfony\Component\HttpKernel\Event\RequestEvent $event
   *   Passed event.
   */
  public function groupexSessionStart(RequestEvent $event) {
    $request = $event->getRequest();
    $uri = str_replace(base_path(), '/', $request->getRequestUri());
    if ($url_object = \Drupal::service('path.validator')->getUrlIfValid($uri)) {
      $route_name = $url_object->getRouteName();
      if ($route_name == 'openy_group_schedules.all_schedules_search' || $route_name == 'ymca_frontend.location_schedules') {
        $session = \Drupal::service('session_configuration');
        $options = $session->getOptions($request);
        if (isset($options['name']) && !$request->cookies->get($options['name'])) {
          $request->cookies->add([$options['name']]);
          session_set_cookie_params(0, $request->getRequestUri());
          session_start();
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::REQUEST][] = ['groupexSessionStart', 10000];

    return $events;
  }

}
