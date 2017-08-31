<?php

namespace Drupal\scheduler_api_test;

use Drupal\scheduler\SchedulerEvent;
use Drupal\scheduler\SchedulerEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Tests events fired on entity objects.
 *
 * These events allow modules to react to the Scheduler process being performed.
 * They are all triggered during Scheduler cron processing with the exception of
 * 'pre_publish_immediately' and 'publish_immediately' which are triggered from
 * scheduler_node_presave().
 *
 * The tests use the standard 'sticky' and 'promote' fields as a simple way to
 * check the processing. Use extra conditional checks on $node->isPublished() to
 * make the tests stronger so they fail if the calls are in the wrong place.
 *
 * To allow this API test module to be enabled interactively (for development
 * and testing) we must avoid unwanted side-effects on other non-test nodes.
 * This is done simply by checking that the node title starts with 'API TEST'.
 *
 * @group scheduler_api_test
 */
class EventSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    // The values in the arrays give the function names below.
    $events[SchedulerEvents::PRE_PUBLISH][] = ['apiTestPrePublish'];
    $events[SchedulerEvents::PUBLISH][] = ['apiTestPublish'];
    $events[SchedulerEvents::PRE_UNPUBLISH][] = ['apiTestPreUnpublish'];
    $events[SchedulerEvents::UNPUBLISH][] = ['apiTestUnpublish'];
    $events[SchedulerEvents::PRE_PUBLISH_IMMEDIATELY][] = ['apiTestPrePublishImmediately'];
    $events[SchedulerEvents::PUBLISH_IMMEDIATELY][] = ['apiTestPublishImmediately'];
    return $events;
  }

  /**
   * Operations to perform before Scheduler publishes a node.
   *
   * @param \Drupal\scheduler\SchedulerEvent $event
   *   The scheduler event.
   */
  public function apiTestPrePublish(SchedulerEvent $event) {
    /** @var \Drupal\node\Entity\Node $node */
    $node = $event->getNode();
    // Before publishing a node make it sticky.
    if (!$node->isPublished() && strpos($node->title->value, 'API TEST') === 0) {
      $node->setSticky(TRUE);
      $event->setNode($node);
    }
  }

  /**
   * Operations to perform before Scheduler unpublishes a node.
   *
   * @param \Drupal\scheduler\SchedulerEvent $event
   *   The scheduler event.
   */
  public function apiTestPreUnpublish(SchedulerEvent $event) {
    /** @var \Drupal\node\Entity\Node $node */
    $node = $event->getNode();
    if ($node->isPublished() && strpos($node->title->value, 'API TEST') === 0) {
      // Before unpublishing a node make it unsticky.
      $node->setSticky(FALSE);
      $event->setNode($node);
    }
  }

  /**
   * Operations before Scheduler publishes a node immediately not via cron.
   *
   * @param \Drupal\scheduler\SchedulerEvent $event
   *   The scheduler event.
   */
  public function apiTestPrePublishImmediately(SchedulerEvent $event) {
    /** @var \Drupal\node\Entity\Node $node */
    $node = $event->getNode();
    // Before publishing immediately set the node to sticky.
    if (!$node->isPromoted() && strpos($node->title->value, 'API TEST') === 0) {
      $node->setSticky(TRUE);
      $event->setNode($node);
    }
  }

  /**
   * Operations to perform after Scheduler publishes a node.
   *
   * @param \Drupal\scheduler\SchedulerEvent $event
   *   The scheduler event.
   */
  public function apiTestPublish(SchedulerEvent $event) {
    /** @var \Drupal\node\Entity\Node $node */
    $node = $event->getNode();
    // After publishing a node promote it to the front page.
    if ($node->isPublished() && strpos($node->title->value, 'API TEST') === 0) {
      $node->setPromoted(TRUE);
      $event->setNode($node);
    }
  }

  /**
   * Operations to perform after Scheduler unpublishes a node.
   *
   * @param \Drupal\scheduler\SchedulerEvent $event
   *   The scheduler event.
   */
  public function apiTestUnpublish(SchedulerEvent $event) {
    /** @var \Drupal\node\Entity\Node $node */
    $node = $event->getNode();
    // After unpublishing a node remove it from the front page.
    if (!$node->isPublished() && strpos($node->title->value, 'API TEST') === 0) {
      $node->setPromoted(FALSE);
      $event->setNode($node);
    }
  }

  /**
   * Operations after Scheduler publishes a node immediately not via cron.
   *
   * @param \Drupal\scheduler\SchedulerEvent $event
   *   The scheduler event.
   */
  public function apiTestPublishImmediately(SchedulerEvent $event) {
    /** @var \Drupal\node\Entity\Node $node */
    $node = $event->getNode();
    // After publishing immediately set the node to promoted and change the
    // title.
    if (!$node->isPromoted() && strpos($node->title->value, 'API TEST') === 0) {
      $node->setTitle('Published immediately')
        ->setPromoted(TRUE);
      $event->setNode($node);
    }
  }

}
