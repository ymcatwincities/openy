<?php
/**
 * @file
 * Event subscriber.
 */

namespace Drupal\ymca_personify\EventSubscriber;

use Drupal\Core\Database\Database;
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
      if (\Drupal::getContainer()->get('current_user')->id() == 0) {
        $account = \Drupal::getContainer()
          ->get('entity.manager')
          ->getStorage('user')
          ->load(1);
        user_login_finalize($account);

        // Update the user table timestamp noting user has logged in.
        Database::getConnection()->update('users_field_data')
          ->fields(array('login' => time()))
          ->condition('uid', 1)
          ->execute();
      }
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
