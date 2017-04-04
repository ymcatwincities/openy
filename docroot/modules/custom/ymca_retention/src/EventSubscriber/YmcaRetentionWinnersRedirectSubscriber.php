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
    if ($route != 'page_manager.page_view_ymca_retention_challenge_ymca_retention_challenge') {
      return;
    }

    $config = \Drupal::config('ymca_retention.general_settings');
    $current_date = new \DateTime();
    $winners_announcement_date = new \DateTime($config->get('date_winners_announcement'));
    $winners_diff = $current_date->diff($winners_announcement_date);

    // Redirect if time winners announcement date in the past.
    if ($winners_diff->invert) {
      $url = Url::fromRoute('page_manager.page_view_ymca_retention_challenge_ymca_retention_challenge');
      $response = new RedirectResponse($url->toString() . '/winners', 302);
      $event->setResponse($response);
    }
  }

}
