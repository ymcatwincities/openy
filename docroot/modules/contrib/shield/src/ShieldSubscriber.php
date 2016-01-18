<?php
/**
 * @file
 * Contains \Drupal\shield\ShieldSubscriber.
 */
namespace Drupal\shield;

use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Provides a ShieldSubscriber.
 */
class ShieldSubscriber implements EventSubscriberInterface {
  /**
   * // only if KernelEvents::REQUEST !!!
   * @see Symfony\Component\HttpKernel\KernelEvents for details
   *
   * @param \Symfony\Component\HttpKernel\Event\GetResponseEvent $event
   *   The Event to process.
   */
  public function ShieldLoad(GetResponseEvent $event) {
    // Get config.
    $config = \Drupal::config('shield.config');
    // Do nothing if disabled.
    if (!$config->get('enabled')) {
      return;
    }

    // Retrieve the user and pass from config.
    $user = $config->get('login');
    $pass = $config->get('password');

    // Test basic auth credentials.
    if (!empty($_SERVER['PHP_AUTH_USER'])
      && isset($_SERVER['PHP_AUTH_PW'])
      && $_SERVER['PHP_AUTH_USER'] === $user
      && $_SERVER['PHP_AUTH_PW']   === $pass
    ) {
      // Authentication passes, do nothing.
      return;
    } else {
      // Authentication failed, print the authorization headers and end the request.
      $print = $config->get('message');
      header(sprintf('WWW-Authenticate: Basic realm="%s"', strtr($print, array('[user]' => $user, '[pass]' => $pass))));
      header('HTTP/1.0 401 Unauthorized');
      exit;
    }
  }

  /**
   * {@inheritdoc}
   */
  static function getSubscribedEvents() {
    // Add ShieldLoad as a subscriber to the request event.
    $events[KernelEvents::REQUEST][] = array('ShieldLoad', 20);
    return $events;
  }
}