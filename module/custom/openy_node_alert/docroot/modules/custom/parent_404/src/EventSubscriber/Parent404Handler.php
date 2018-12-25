<?php

namespace Drupal\parent_404\EventSubscriber;

use Drupal\Core\EventSubscriber\HttpExceptionSubscriberBase;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;

/**
 * Parent 404 exception handler.
 */
class Parent404Handler extends HttpExceptionSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected static function getPriority() {
    return 201;
  }

  /**
   * {@inheritdoc}
   */
  protected function getHandledFormats() {
    return ['html'];
  }

  /**
   * Redirect user to parent page.
   *
   * @param GetResponseForExceptionEvent $event
   *   Event.
   */
  public function on404(GetResponseForExceptionEvent $event) {
    $request = $event->getRequest();
    $request_path = $request->getPathInfo();
    $items = explode('/', $request_path);
    $dir = $items[1];
    $to_direct = ['blog', 'news'];
    if (in_array($dir, $to_direct)) {
      $redirect = new RedirectResponse($request->getBaseUrl() . '/' . $dir, 303);
      $event->setResponse($redirect);
    }
  }

}
