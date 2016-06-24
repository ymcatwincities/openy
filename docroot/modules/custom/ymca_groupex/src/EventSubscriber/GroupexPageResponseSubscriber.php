<?php

namespace Drupal\ymca_groupex\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
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
   * @param \Symfony\Component\HttpKernel\Event\GetResponseEvent $event
   *   Passed event.
   */
  public function groupexSessionStart(GetResponseEvent $event) {
    $request = $event->getRequest();
    if ($url_object = \Drupal::service('path.validator')->getUrlIfValid($request->getRequestUri())) {
      $route_name = $url_object->getRouteName();
      if ($route_name == 'ymca_groupex.all_schedules_search' || $route_name == 'ymca_frontend.location_schedules') {
        if (session_status() == PHP_SESSION_NONE) {
          $session = \Drupal::service('session_configuration');
          $options = $session->getOptions($request);
          $request->cookies->add([$options['name'] => TRUE]);

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
    $events[KernelEvents::REQUEST][] = array('groupexSessionStart', 10000);

    return $events;
  }

}
