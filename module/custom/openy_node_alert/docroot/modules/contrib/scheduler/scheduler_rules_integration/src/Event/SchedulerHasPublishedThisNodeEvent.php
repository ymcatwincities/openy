<?php

namespace Drupal\scheduler_rules_integration\Event;

use Drupal\node\NodeInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * A node is published by Scheduler.
 *
 * This event is fired when Scheduler publishes a node via cron.
 */
class SchedulerHasPublishedThisNodeEvent extends Event {

  const EVENT_NAME = 'scheduler_has_published_this_node_event';

  /**
   * The node which has been processed.
   *
   * @var Drupal\node\NodeInterface
   */
  public $node;

  /**
   * Constructs the object.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node which has been published by Scheduler.
   */
  public function __construct(NodeInterface $node) {
    $this->node = $node;
  }

}
