<?php

namespace Drupal\ymca_retention\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * KernelEvents::REQUEST subscriber for disabling cache on retention pages.
 */
class YmcaRetentionSubscriber implements EventSubscriberInterface {

  /**
   * Disable cache for retention pages.
   *
   * @param \Symfony\Component\HttpKernel\Event\GetResponseEvent $event
   *   Event.
   */
  public function disableCacheRetentionPages(GetResponseEvent $event) {
    $route = \Drupal::service('current_route_match')->getRouteName();
    if (is_int(stripos($route, 'page_manager.page_view_ymca_retention_challenge'))) {
      \Drupal::service('page_cache_kill_switch')->trigger();
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events = [];
    $events[KernelEvents::REQUEST][] = ['disableCacheRetentionPages'];
    return $events;
  }

}
