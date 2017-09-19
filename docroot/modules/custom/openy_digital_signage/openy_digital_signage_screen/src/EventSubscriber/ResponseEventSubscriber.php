<?php

namespace Drupal\openy_digital_signage_screen\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
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
   * @param \Symfony\Component\HttpKernel\Event\FilterResponseEvent $event
   *   Event.
   */
  public function onRespond(FilterResponseEvent $event) {
    $response = $event->getResponse();
    $routes = ['entity.openy_digital_signage_screen.canonical'];
    if (in_array(\Drupal::service('current_route_match')->getRouteName(), $routes)) {
      if ($response->headers->has('x-frame-options')) {
        $response->headers->remove('x-frame-options');
      }
    }
  }

}
