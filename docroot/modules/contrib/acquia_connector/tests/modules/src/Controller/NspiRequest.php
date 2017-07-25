<?php

namespace Drupal\acquia_connector_test\Controller;

use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class NspiRequest.
 *
 * @package Drupal\acquia_connector_test\Controller.
 */
class NspiRequest implements EventSubscriberInterface {

  /**
   * Counts requests to the test NSPI server.
   *
   * @param GetResponseEvent $event
   *   The kernel request event.
   */
  public function onKernelRequest(GetResponseEvent $event) {
    $route = $event->getRequest()->attributes->get('_route');
    $patch = explode(".", $route);
    if ((isset($patch['0']) && $patch['0'] == 'acquia_connector_test')) {
      $requests = \Drupal::state()->get('acquia_connector_test_request_count', 0);
      $requests++;
      \Drupal::state()->set('acquia_connector_test_request_count', $requests);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::REQUEST][] = array('onKernelRequest');
    return $events;
  }

}
