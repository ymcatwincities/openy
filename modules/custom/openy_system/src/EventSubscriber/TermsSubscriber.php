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

  /**
   * {@inheritdoc}
   */
  public function checkForRedirection(GetResponseEvent $event) {
    $config = \Drupal::config('openy.terms_and_conditions.schema');
    $current_user = \Drupal::currentUser();
    $url = Url::fromRoute('openy_system.openy_terms_and_conditions')
      ->toString();
    $request_uri = $event->getRequest()->getRequestUri();
    if (!$config->get('accepted_version') && $request_uri != $url && $current_user->isAuthenticated()) {
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
