<?php

namespace Drupal\openy_hf\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Class ResponseEventSubscriber.
 *
 * Implements EventSubscriber functionality.
 */
class ResponseEventSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    // Run Event on the latest stage, after all modules did their modifications
    // to Headers.
    $events[KernelEvents::RESPONSE][] = ['onRespond', -1000];
    return $events;
  }

  /**
   * Event callback onRespond.
   *
   * @param \Symfony\Component\HttpKernel\Event\RequestEvent $event
   *   Event.
   */
  public function onRespond(RequestEvent $event) {
    $remove_header = FALSE;
    $response = $event->getResponse();
    $routes = ['openy_hf.header', 'openy_hf.footer'];
    if (in_array(\Drupal::service('current_route_match')->getRouteName(), $routes)) {
      $remove_header = TRUE;
    }
    if ($remove_header && $response->headers->has('x-frame-options')) {
      $response->headers->remove('x-frame-options');
    }
  }

}
