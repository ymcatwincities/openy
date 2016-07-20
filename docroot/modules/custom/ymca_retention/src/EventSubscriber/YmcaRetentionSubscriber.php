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
    if ($route == 'page_manager.page_view_ymca_retention_pages_y_games_activity') {
      $settings = \Drupal::config('ymca_retention.general_settings');
      $from_date = new \DateTime($settings->get('date_reporting_open'));
      $to_date = new \DateTime($settings->get('date_reporting_close'));
      $current_date = new \DateTime();
      if ($current_date < $from_date || $current_date > $to_date) {
        $this->redirectResponse($event);
      }
    }
    $member_id = AnonymousCookieStorage::get('ymca_retention_member');
    if (empty($member_id)) {
      $this->redirectResponse($event);
    }
  }

  /**
   * Set redirect response to event.
   *
   * @param \Symfony\Component\HttpKernel\Event\GetResponseEvent $event
   *   Event.
   */
  public function redirectResponse(GetResponseEvent $event) {
    $url = Url::fromRoute('page_manager.page_view_ymca_retention_campaign', [], [
      'absolute' => TRUE,
    ])->toString();
    $event->setResponse(new RedirectResponse($url));
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
