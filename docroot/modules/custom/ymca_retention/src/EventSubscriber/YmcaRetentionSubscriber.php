<?php

namespace Drupal\ymca_retention\EventSubscriber;

use Drupal\Core\Url;
use Drupal\ymca_retention\AnonymousCookieStorage;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
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
    if (is_int(stripos($route, 'page_manager.page_view_ymca_retention'))) {
      \Drupal::service('page_cache_kill_switch')->trigger();
    }
  }

  /**
   * Check and redirect to landing page in case when user is not identified.
   *
   * @param \Symfony\Component\HttpKernel\Event\GetResponseEvent $event
   *   Event.
   */
  public function checkAccessRedirect(GetResponseEvent $event) {
    $route = \Drupal::service('current_route_match')->getRouteName();
    $redirect_routes = [
      'page_manager.page_view_ymca_retention_pages_y_games_enroll_success',
      'page_manager.page_view_ymca_retention_pages_y_games_activity',
    ];
    if (!in_array($route, $redirect_routes)) {
      return;
    }
    $member_id = AnonymousCookieStorage::get('ymca_retention_member');
    if (empty($member_id)) {
      $url = Url::fromRoute('page_manager.page_view_ymca_retention_campaign', [], [
        'absolute' => TRUE,
      ])->toString();
      $event->setResponse(new RedirectResponse($url));
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events = [];
    $events[KernelEvents::REQUEST][] = ['disableCacheRetentionPages'];
    $events[KernelEvents::REQUEST][] = ['checkAccessRedirect'];
    return $events;
  }

}
