<?php
/**
 * @file
 * Event subscriber.
 */

namespace Drupal\ymca_personify\EventSubscriber;

use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\Core\Url;
use Drupal\personify_sso\PersonifySso;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class PersonifySubscriber
 * @package Drupal\ymca_personify\EventSubscriber.
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
   * Redirect user to Personify SSO server.
   *
   * @param \Symfony\Component\HttpKernel\Event\GetResponseEvent $event
   */
  public function personifyRedirect(GetResponseEvent $event) {
    if (!is_null($event->getRequest()->query->get('login'))) {
      $options = [
        'absolute' => TRUE,
        'query' => [
          'authorize' => 1,
        ],
      ];
      $url = Url::fromUserInput('/', $options)->toString();

      $this->initPersonifySso();

      $vendor_token = $this->sso->getVendorToken($url);
      $options = [
        'query' => [
          'vi' => $this->config['vendor_id'],
          'vt' => $vendor_token,
        ],
      ];
      $redirect_url = Url::fromUri($this->config['url_login'], $options)->toString();
      $redirect = TrustedRedirectResponse::create($redirect_url);
      $event->setResponse($redirect);
    }
  }

  /**
   * Check customer token and login.
   *
   * @param \Symfony\Component\HttpKernel\Event\GetResponseEvent $event
   */
  public function personifyLogin(GetResponseEvent $event) {
    $query = $event->getRequest()->query;
    if ($query->get('authorize') && $query->get('ct')) {
      $this->initPersonifySso();
      $customer_token = $query->get('ct');
      $decrypted_token = $this->sso->decryptCustomerToken($customer_token);
      if ($this->sso->validateCustomerToken($decrypted_token)) {
        // Yes, you are welcome!
      }
      else {
        // Something went wrong. Show message. Log error.
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
      ['personifyRedirect'],
      ['personifyLogin'],
    ];
    return $events;
  }

}
