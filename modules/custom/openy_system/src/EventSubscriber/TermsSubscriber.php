<?php

namespace Drupal\openy_system\EventSubscriber;

use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Openy event subscriber.
 */
class TermsSubscriber implements EventSubscriberInterface {

  protected $config;
  protected $currentUser;

  public function __construct($config_factory, $current_user) {
    $this->config = $config_factory->get('openy.terms_and_conditions.schema');
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public function checkForRedirection(GetResponseEvent $event) {
    $url = Url::fromRoute('openy_system.openy_terms_and_conditions')
      ->toString();
    $request_uri = $event->getRequest()->getRequestUri();

    $is_authenticated_user = $this->currentUser->isAuthenticated();
    $is_on_terms_page = $request_uri == $url;
    $is_accepted_terms = $this->config->get('accepted_version');
    $is_analytics_state_decided = $this->config->get('analytics_optin');

    if(!$is_authenticated_user || $is_on_terms_page) {
      return;
    }

    if (!$is_accepted_terms || !$is_analytics_state_decided) {
      $event->setResponse(new RedirectResponse($url, 302));
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::REQUEST][] = array('checkForRedirection');
    return $events;
  }

}
