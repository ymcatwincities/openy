<?php
/**
 * @file
 * Contains \Drupal\ymca_personify\EvenSubscriber\PersonifySubscriber.
 */

namespace Drupal\ymca_personify\EventSubscriber;

use Drupal\personify_sso\PersonifySso;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class PersonifySubscriber.
 */
class PersonifySubscriber implements EventSubscriberInterface {

  /**
   * PersonifySso instance.
   *
   * @var PersonifySso
   */
  private $sso = NULL;

  /**
   * Config.
   *
   * @var array
   */
  private $config = [];

  /**
   * Check customer token and login.
   *
   * @param \Symfony\Component\HttpKernel\Event\GetResponseEvent $event
   */
  public function personifyLogin(GetResponseEvent $event) {
    $query = $event->getRequest()->query;
    if ($query->get('personify') && $query->get('ct')) {
      $this->initPersonifySso();
      $customer_token = $query->get('ct');
      $decrypted_token = $this->sso->decryptCustomerToken($customer_token);
      if ($token = $this->sso->validateCustomerToken($decrypted_token)) {
        \Drupal::logger('ymca_personify')->info('A user logged in via Personify.');
        $session = \Drupal::service('session_manager');
        $_SESSION['personify_token'] = $token;
        $session->start();
      }
      else {
        \Drupal::logger('ymca_personify')->warning('An attempt to login with wrong personify token was detected.');
      }
    }
  }

  /**
   * Initialize PersonifySso object.
   */
  private function initPersonifySso() {
    $this->config = \Drupal::config('ymca_personify.settings')->getRawData();
    if (is_null($this->sso)) {
      $this->sso = new PersonifySso(
        $this->config['wsdl'],
        $this->config['vendor_id'],
        $this->config['vendor_username'],
        $this->config['vendor_password'],
        $this->config['vendor_block']
      );
    }
  }

  /**
   * {@inheritdoc}
   */
  static function getSubscribedEvents() {
    $events[KernelEvents::REQUEST] = [
      ['personifyLogin'],
    ];
    return $events;
  }

}
