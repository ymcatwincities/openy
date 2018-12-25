<?php

namespace Drupal\scheduler_rules_integration\Event;

use Drupal\node\NodeInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * An existing node is scheduled for unpublishing.
 *
 * This event is fired when an existing node is updated/saved and it has a
 * scheduled unpublishing date.
 */
class ExistingNodeIsScheduledForUnpublishingEvent extends Event {

  const EVENT_NAME = 'scheduler_existing_node_is_scheduled_for_unpublishing_event';

  /**
   * The node which is being scheduled and saved.
   *
   * @var Drupal\node\NodeInterface
   */
  public $node;

  /**
   * Constructs the object.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node which is being scheduled and saved.
   */
  public function __construct(NodeInterface $node) {
    $this->node = $node;
  }

}
