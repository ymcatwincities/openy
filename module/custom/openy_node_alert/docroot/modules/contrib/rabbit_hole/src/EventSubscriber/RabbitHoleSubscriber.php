<?php

namespace Drupal\rabbit_hole\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\EventDispatcher\Event;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\rabbit_hole\BehaviorInvoker;

/**
 * Class EventSubscriber.
 *
 * @package Drupal\rabbit_hole
 */
class RabbitHoleSubscriber implements EventSubscriberInterface {

  /**
   * Drupal\rabbit_hole\BehaviorInvoker definition.
   *
   * @var Drupal\rabbit_hole\BehaviorInvoker
   */
  protected $rabbitHoleBehaviorInvoker;

  /**
   * Constructor.
   */
  public function __construct(BehaviorInvoker $rabbit_hole_behavior_invoker) {
    $this->rabbitHoleBehaviorInvoker = $rabbit_hole_behavior_invoker;
  }

  /**
   * {@inheritdoc}
   */
  static public function getSubscribedEvents() {
    $events['kernel.request'] = ['onRequest'];
    $events['kernel.response'] = ['onResponse'];
    return $events;
  }

  /**
   * A method to be called whenever a kernel.request event is dispatched.
   *
   * It invokes a rabbit hole behavior on an entity in the request if
   * applicable.
   *
   * @param \Symfony\Component\EventDispatcher\Event $event
   *   The event triggered by the request.
   */
  public function onRequest(Event $event) {
    return $this->processEvent($event);
  }

  /**
   * A method to be called whenever a kernel.response event is dispatched.
   *
   * Like the onRequest event, it invokes a rabbit hole behavior on an entity in
   * the request if possible. Unlike the onRequest event, it also passes in a
   * response.
   *
   * @param \Symfony\Component\EventDispatcher\Event $event
   *   The event triggered by the response.
   */
  public function onResponse(Event $event) {
    return $this->processEvent($event);
  }

  /**
   * Process events generically invoking rabbit hole behaviors if necessary.
   *
   * @param \Symfony\Component\EventDispatcher\Event $event
   *   The event to process.
   */
  private function processEvent(Event $event) {
    // Don't process events with HTTP exceptions - those have either been thrown
    // by us or have nothing to do with rabbit hole.
    if ($event->getRequest()->get('exception') != NULL) {
      return;
    }

    // We won't go ahead if we have an entity form (i.e. we're adding/editing
    // an entity).
    if ($event->getRequest()->get('_entity_form') == NULL) {
      // We check for all of our known entity keys that work with rabbit hole
      // and invoke rabbit hole behavior on the first one we find (which
      // should also be the only one).
      $entity_keys = $this->rabbitHoleBehaviorInvoker->getPossibleEntityTypeKeys();
      foreach ($entity_keys as $ekey) {
        $entity = $event->getRequest()->get($ekey);
        if (isset($entity) && $entity instanceof ContentEntityInterface) {
          $new_response = $this->rabbitHoleBehaviorInvoker
            ->processEntity($entity, $event->getResponse());
          if (isset($new_response)) {
            $event->setResponse($new_response);
          }
          break;
        }
      }
    }
  }

}
