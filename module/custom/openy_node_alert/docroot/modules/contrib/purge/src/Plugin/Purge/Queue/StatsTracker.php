<?php

namespace Drupal\purge\Plugin\Purge\Queue;

use Drupal\Core\State\StateInterface;
use Drupal\purge\Counter\PersistentCounter;
use Drupal\purge\Plugin\Purge\Queue\StatsTrackerInterface;

/**
 * Provides the statistics tracker.
 */
class StatsTracker implements StatsTrackerInterface {

  /**
   * Buffer of values that need to be written back to state storage. Items
   * present in the buffer take priority over state data.
   *
   * @var float[]
   */
  protected $buffer = [];

  /**
   * The counter tracking how many invalidations are claimed right now.
   *
   * @var \Drupal\purge\Counter\PersistentCounterInterface
   */
  protected $claimed;

  /**
   * The counter tracking how many invalidations have been deleted.
   *
   * @var \Drupal\purge\Counter\PersistentCounterInterface
   */
  protected $deleted;

  /**
   * The counter tracking the total amount of invalidations in the queue.
   *
   * @var \Drupal\purge\Counter\PersistentCounterInterface
   */
  protected $total;

  /**
   * The state key value store.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * Mapping of counter objects and state key names.
   *
   * @var string[]
   */
  protected $stateKeys = [
    'claimed' => 'purge_queue_claimed',
    'deleted' => 'purge_queue_deleted',
    'total' => 'purge_queue_total',
  ];

  /**
   * Construct a statistics tracker.
   *
   * @param \Drupal\Core\State\StateInterface $state
   *   The state key value store.
   */
  public function __construct(StateInterface $state) {
    $this->state = $state;
    $this->initializeCounters();
  }

  /**
   * Intialize or reinitialize the counter objects.
   */
  protected function initializeCounters() {

    // Prefetch counter values from either the local buffer or the state API.
    $values = $this->state->getMultiple($this->stateKeys);
    foreach ($this->stateKeys as $counter => $key) {
      if (isset($this->buffer[$key])) {
        $values[$key] = $this->buffer[$key];
      }
      if (!isset($values[$key])) {
        $values[$key] = 0;
      }

      // Instantiate (or overwrite) the counter objects and pass a closure as
      // write callback. The closure writes changed values to $this->buffer.
      $this->$counter = new PersistentCounter($values[$key]);
      $this->$counter->disableSet();
      $this->$counter->setWriteCallback($key, function ($id, $value) {
        $this->buffer[$id] = $value;
      });
    }

    // As deleted and total can only increase, disable decrementing on them.
    $this->deleted->disableDecrement();
    $this->total->disableDecrement();
  }

  /**
   * {@inheritdoc}
   */
  public function claimed() {
    return $this->claimed;
  }

  /**
   * {@inheritdoc}
   */
  public function deleted() {
    return $this->deleted;
  }

  /**
   * {@inheritdoc}
   */
  public function total() {
    return $this->total;
  }

  /**
   * {@inheritdoc}
   */
  public function destruct() {

    // When the buffer contains changes, write them to the state API in one go.
    if (count($this->buffer)) {
      $this->state->setMultiple($this->buffer);
      $this->buffer = [];
    }
  }

  /**
   * Wipe all statistics data.
   */
  public function wipe() {
    $this->buffer = [];
    $this->state->deleteMultiple($this->stateKeys);
    $this->initializeCounters();
  }

}
