<?php

namespace Drupal\purge\Plugin\Purge\Queue;

use Drupal\Core\DestructableInterface;

/**
 * Describes the statistics tracker.
 *
 * The statistics tracker keeps track of queue activity by actively counting how
 * many items the queue currently holds and how many have been deleted or
 * released back to it. This data can be used to report progress on the queue
 * and is easily retrieved, the data resets when the queue is emptied.
 */
interface StatsTrackerInterface extends DestructableInterface {

  /**
   * Get the counter tracking how many invalidations are claimed right now.
   *
   * @return \Drupal\purge\Counter\PersistentCounterInterface
   */
  public function claimed();

  /**
   * Get the counter tracking how many invalidations have been deleted.
   *
   * @return \Drupal\purge\Counter\PersistentCounterInterface
   */
  public function deleted();

  /**
   * Get the counter tracking the total amount of invalidations in the queue.
   *
   * @return \Drupal\purge\Counter\PersistentCounterInterface
   */
  public function total();

  /**
   * Wipe all statistics data.
   */
  public function wipe();
}
