<?php
/**
 * @file
 * Event subscriber.
 */

namespace Drupal\ymca_personify\EventSubscriber;

use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class PersonifySubscriber
 * @package Drupal\ymca_personify\EventSubscriber.
 */
class PersonifySubscriber implements EventSubscriberInterface {

  /**
   * Check for login
   */
  public function checkForLogin(GetResponseEvent $event) {
    if ($event->getRequest()->query->get('login-me')) {
    }
  }

  /**
   * {@inheritdoc}
   */
  static function getSubscribedEvents() {
    $events[KernelEvents::REQUEST][] = ['checkForLogin'];
    return $events;
  }

}
