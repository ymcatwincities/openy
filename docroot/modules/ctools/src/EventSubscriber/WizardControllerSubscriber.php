<?php

/**
 * @file
 * Contains \Drupal\ctools\EventSubscriber\WizardControllerSubscriber.
 */

namespace Drupal\ctools\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Sets the request format onto the request object.
 */
class WizardControllerSubscriber implements EventSubscriberInterface {

  /**
   * Sets the _controller on a request when a _wizard is defined.
   *
   * @param \Symfony\Component\HttpKernel\Event\GetResponseEvent $event
   *   The event to process.
   */
  public function onRequestDeriveFormWrapper(GetResponseEvent $event) {
    $request = $event->getRequest();

    if ($request->attributes->has('_wizard')) {
      $request->attributes->set('_controller', 'ctools.wizard.form:getContentResult');
    }
    if ($request->attributes->has('_entity_wizard')) {
      $request->attributes->set('_controller', 'ctools.wizard.entity.form:getContentResult');
    }
  }

  /**
   * Registers the methods in this class that should be listeners.
   *
   * @return array
   *   An array of event listener definitions.
   */
  static function getSubscribedEvents() {
    $events[KernelEvents::REQUEST][] = array('onRequestDeriveFormWrapper', 29);
    return $events;
  }

}
