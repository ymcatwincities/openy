<?php

namespace Drupal\ymca_personify\EventSubscriber;

use Drupal\personify_sso\PersonifySso;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class TokenChecker.
 *
 * @package Drupal\ymca_personify\EventSubscriber
 */
class TokenChecker implements EventSubscriberInterface {

  /**
   * Waiting period.
   *
   * @var int
   */
  const CHECK_TIME = 60;

  /**
   * Check whether user is logged in Personify.
   *
   * @param \Symfony\Component\HttpKernel\Event\RequestEvent $event
   *   Event.
   */
  public function checkToken(RequestEvent $event) {
    $request_time = \Drupal::time()->getRequestTime();
    $container = \Drupal::getContainer();

    /** @var Request $request */
    $request = $container->get('request_stack')->getCurrentRequest();

    $token = $request->cookies->get('Drupal_visitor_personify_authorized');
    $time = $request->cookies->get('Drupal_visitor_personify_time');

    // Do not check if user is not authorized.
    if (empty($token)) {
      return;
    }

    // Do not check on every request.
    if (!empty($time) && ($request_time - $time) < self::CHECK_TIME) {
      return;
    }

    // Skip checking for the next routes.
    $route_match = \Drupal::getContainer()->get('current_route_match');
    $disabled_routes = [
      'ymca_personify.personify_login',
      'ymca_personify.personify_auth',
      'ymca_personify.personify_signout',
    ];
    $route_name = $route_match->getRouteName();
    if (in_array($route_name, $disabled_routes)) {
      return;
    }

    $config = \Drupal::config('ymca_personify.settings')->getRawData();
    $sso = new PersonifySso(
      $config['wsdl'],
      $config['vendor_id'],
      $config['vendor_username'],
      $config['vendor_password'],
      $config['vendor_block']
    );

    if ($token = $sso->validateCustomerToken($token)) {
      user_cookie_save([
        'personify_authorized' => $token,
        'personify_time' => $request_time,
      ]);
    }
    else {
      user_cookie_delete('personify_authorized');
      user_cookie_delete('personify_time');
    }
  }

  /**
   * {@inheritdoc}
   */
  static public function getSubscribedEvents() {
    $events[KernelEvents::REQUEST][] = ['checkToken'];
    return $events;
  }

}
