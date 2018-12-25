<?php

namespace Drupal\acquia_search_test\EventSubscriber;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class AcquiaSearchTestSubscriber implements EventSubscriberInterface {

  /**
   * Injects $_GET parameters from URLs into the some global $_ENV.
   */
  public function checkForOverrides(GetResponseEvent $event) {
    if ($event->getRequest()->query->get('env-overrides')) {
      $allowed_keys = [
        'AH_SITE_ENVIRONMENT',
        'AH_SITE_NAME',
        'AH_SITE_GROUP',
        'AH_PRODUCTION',
      ];
      foreach ($allowed_keys as $key) {
        $value = $event->getRequest()->query->get($key);
        if (!empty($value)) {
          drupal_set_message('acquia_search_test() module set $_ENV[' . $key . '] to ' . $value);
          $_ENV[$key] = $value;
        }
      }
    }
  }

  /**
  * {@inheritdoc}
  */
  public static function getSubscribedEvents() {
    // Add our event with a high priority (1000) to ensure it runs before
    // the Solr connection is decided on.
    $events[KernelEvents::REQUEST][] = array('checkForOverrides', 1000);
    return $events;
  }

}
