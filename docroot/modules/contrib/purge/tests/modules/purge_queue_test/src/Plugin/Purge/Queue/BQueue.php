<?php

namespace Drupal\purge_queue_test\Plugin\Purge\Queue;

use Drupal\purge\Plugin\Purge\Queue\MemoryQueue;
use Drupal\purge\Plugin\Purge\Queue\QueueInterface;

/**
 * A \Drupal\purge\Plugin\Purge\Queue\QueueInterface compliant memory queue for testing.
 *
 * @PurgeQueue(
 *   id = "b",
 *   label = @Translation("Memqueue B"),
 *   description = @Translation("A volatile and non-persistent memory queue"),
 * )
 */
class BQueue extends MemoryQueue implements QueueInterface {}
