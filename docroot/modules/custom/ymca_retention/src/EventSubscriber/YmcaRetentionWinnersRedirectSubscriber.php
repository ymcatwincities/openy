<?php

namespace Drupal\ymca_retention\EventSubscriber;

use Drupal\Core\Url;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * KernelEvents::REQUEST subscriber for redirecting on Winner page.
 */
class YmcaRetentionWinnersRedirectSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return ([
      KernelEvents::REQUEST => [
        ['redirectToWinnersPage'],
      ],
    ]);
  }

  /**
   * Redirect requests for /challenge, if time winners announcement date in the past.
   *
   * @param GetResponseEvent $event
   *   Event.
   */
  public function redirectToWinnersPage(GetResponseEvent $event) {
    $routes = [
      'challenge' => 'page_manager.page_view_ymca_retention_challenge_ymca_retention_challenge',
      'team' => 'page_manager.page_view_ymca_retention_challenge_pages_ymca_retention_challenge_team',
      'upcoming' => 'page_manager.page_view_ymca_retention_challenge_pages_ymca_retention_challenge_upcoming',
      'winners' => 'page_manager.page_view_ymca_retention_challenge_pages_ymca_retention_challenge_winners',
    ];
    $current_route = \Drupal::service('current_route_match')->getRouteName();

    if (!in_array($current_route, $routes)) {
      return;
    }

    $route_id = array_search($current_route, $routes);
    $settings = \Drupal::config('ymca_retention.general_settings');
    $current_date = new \DateTime();
    $date_winners_announcement = new \DateTime($settings->get('date_winners_announcement'));
    $date_campaign_close = new \DateTime($settings->get('date_campaign_close'));

    if ($current_date < $date_campaign_close) {
      return;
    }

    if ($current_date > $date_winners_announcement && 'winners' == $route_id) {
      return;
    }

    if ($current_date < $date_winners_announcement && 'upcoming' == $route_id) {
      return;
    }

    $redirect_id = $current_date > $date_winners_announcement ? 'winners' : 'upcoming';
    $response = new RedirectResponse(URL::fromRoute($routes[$redirect_id], ['string' => $redirect_id])->toString(), 302);
    $event->setResponse($response);
  }

}
