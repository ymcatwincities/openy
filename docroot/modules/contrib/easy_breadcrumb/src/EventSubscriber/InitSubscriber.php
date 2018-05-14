<?php

namespace Drupal\easy_breadcrumb\EventSubscriber;

use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class InitSubscriber.
 *
 * @package Drupal\easy_breadcrumb\EventSubscriber
 */
class InitSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [KernelEvents::REQUEST => ['onEvent', 0]];
  }

  /**
   * {@inheritdoc}
   */
  public function onEvent() {

  }

}
