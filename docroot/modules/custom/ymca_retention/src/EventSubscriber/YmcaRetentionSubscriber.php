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
      'page_manager.page_view_ymca_retention_campaign',
      'page_manager.page_view_ymca_retention_pages_y_games_enroll_success',
      'page_manager.page_view_ymca_retention_pages_y_games_activity',
      'page_manager.page_view_ymca_retention_pages_y_games_team',
    ];
    if (!in_array($route, $redirect_routes)) {
      return;
    }

    $settings = \Drupal::config('ymca_retention.general_settings');
    $current_date = new \DateTime();

    // Redirect to winners page if campaign is closed.
    $date_campaign_close = new \DateTime($settings->get('date_campaign_close'));
    if ($current_date > $date_campaign_close) {
      $this->redirectWinnersPage($event);
    }

    if ($route == 'page_manager.page_view_ymca_retention_pages_y_games_activity') {
      $from_date = new \DateTime($settings->get('date_reporting_open'));
      $to_date = new \DateTime($settings->get('date_reporting_close'));
      if ($current_date < $from_date || $current_date > $to_date) {
        $this->redirectMainPage($event);
      }
    }

    if ($route == 'page_manager.page_view_ymca_retention_pages_y_games_enroll_success'
      || $route == 'page_manager.page_view_ymca_retention_pages_y_games_activity') {
      $member_id = AnonymousCookieStorage::get('ymca_retention_member');
      if (empty($member_id)) {
        $this->redirectMainPage($event);
      }
    }
  }

  /**
   * Set redirect response to event.
   *
   * @param \Symfony\Component\HttpKernel\Event\GetResponseEvent $event
   *   Event.
   */
  public function redirectMainPage(GetResponseEvent $event) {
    $url = Url::fromRoute('page_manager.page_view_ymca_retention_campaign', [], [
      'absolute' => TRUE,
    ])->toString();
    $event->setResponse(new RedirectResponse($url));
  }

  /**
   * Set redirect response to event.
   *
   * @param \Symfony\Component\HttpKernel\Event\GetResponseEvent $event
   *   Event.
   */
  public function redirectWinnersPage(GetResponseEvent $event) {
    $url = Url::fromRoute('page_manager.page_view_ymca_retention_pages', ['string' => 'winners'], [
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
