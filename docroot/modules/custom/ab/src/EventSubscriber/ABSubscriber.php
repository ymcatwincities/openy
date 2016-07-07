<?php

namespace Drupal\ab\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;

/**
 * Class ABSubscriber.
 *
 * @package Drupal\ab
 */
class ABSubscriber implements EventSubscriberInterface {


  /**
   * Constructor.
   */
  public function __construct() {

  }

  /**
   * {@inheritdoc}
   */
  static function getSubscribedEvents() {
    $events['kernel.request'] = ['onKernelRequest'];

    return $events;
  }

  /**
   * This method is called whenever the kernel.request event is
   * dispatched.
   *
   * @param GetResponseEvent $event
   */
  public function onKernelRequest(Event $event) {
    if (!isset($_COOKIE['ab'])) {
      session_set_cookie_params(0);
      $ab = rand(0, 1);
      // Disable Varnish.
      // $_SESSION[$ab] = TRUE;
      setcookie('ab', $ab == 0 ? 'a' : 'b');
      // session_start();
    }
  }

}
