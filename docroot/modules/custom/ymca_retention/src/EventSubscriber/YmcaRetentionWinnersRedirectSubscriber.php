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
    return([
      KernelEvents::REQUEST => [
        ['redirectToWinnersPage'],
      ]
    ]);
  }

  /**
   * Redirect requests for /challenge, if time winners announcement date in the past.
   *
   * @param GetResponseEvent $event
   *   Event.
   */
  public function redirectToWinnersPage(GetResponseEvent $event) {
    $route = \Drupal::service('current_route_match')->getRouteName();

    if (!in_array($route, [
      'page_manager.page_view_ymca_retention_challenge_ymca_retention_challenge',
      'page_manager.page_view_ymca_retention_challenge_pages_ymca_retention_challenge_winners',
    ])) {
      return;
    }

    $url = Url::fromRoute('page_manager.page_view_ymca_retention_challenge_ymca_retention_challenge');
    $response = new RedirectResponse($url->toString() . '/upcoming', 302);
    $event->setResponse($response);
  }

}
