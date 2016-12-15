<?php

namespace Drupal\purge\Plugin\Purge\Purger;

use Drupal\Core\State\StateInterface;
use Drupal\purge\Plugin\Purge\Purger\RuntimeMeasurement;
// use Drupal\purge\Plugin\Purge\Purger\RuntimeMeasurementInterface;
use Drupal\purge\Plugin\Purge\Purger\RuntimeMeasurementTrackerInterface;

/**
 * Provides the tracker that tracks RuntimeMeasurement objects for purgers.
 *
 * This tracker creates RuntimeMeasurement counters for purgers that return TRUE
 * on their ::hasRuntimeMeasurement() implementation. When the counter objects
 * attempt to store a new measurement value, this tracker will store the
 * values for all counters in the underlying storing mechanism.
 */
class RuntimeMeasurementTracker implements RuntimeMeasurementTrackerInterface {

  /**
   * Buffer of values that need to be written back to state storage. Items
   * present in the buffer take priority over state data.
   *
   * @var float[]
   */
  protected $buffer = [];

  /**
   * Holds all loaded purgers plugins.
   *
   * @var \Drupal\purge\Plugin\Purge\Purger\PurgerInterface[]
   */
  protected $purgers = NULL;

  /**
   * The state key value store.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * Mapping of purger IDs and state key names.
   *
   * @var string[]
   */
  protected $stateKeys = [];

  /**
   * Construct a RuntimeMeasurementTracker.
   *
   * @param \Drupal\Core\State\StateInterface $state
   *   The state key value store.
   */
  public function __construct(StateInterface $state) {
    $this->state = $state;
  }

  /**
   * Intialize or reinitialize the counter objects.
   *
   * @throws \LogicException
   *   Thrown when $this->purgers isn't initialized.
   */
  protected function initializeCounters() {
    if (is_null($this->purgers)) {
      throw new \LogicException('$this->purgers is still NULL, call ::setPurgers.');
    }

    // Generate state keys for participating purger plugins.
    $this->stateKeys = [];
    foreach ($this->purgers as $purger) {
      if ($purger->hasRuntimeMeasurement()) {
        $id = $purger->getId();
        $this->stateKeys[$id] = 'purge_purger_measurement_' . $id;
      }
    }

    // Prefetch counter values, instantiate and then associate counter objects.
    if (count($this->stateKeys)) {
      $values = $this->state->getMultiple($this->stateKeys);
      foreach ($this->stateKeys as $id => $key) {
        if (isset($this->buffer[$key])) {
          $values[$key] = $this->buffer[$key];
        }
        if (!isset($values[$key])) {
          $values[$key] = 0.0;
        }

        // Instantiate (or overwrite) the counter objects and pass a closure as
        // write callback. The closure writes changed values to $this->buffer.
        $measurement = new RuntimeMeasurement($values[$key]);
        $measurement->disableDecrement();
        $measurement->disableIncrement();
        $measurement->setWriteCallback($key, function ($id, $value) {
          $this->buffer[$id] = $value;
        });

        // To start and stop measurement, PurgersServiceInterface::invalidate()
        // needs to access ::start() and ::stop(), so we need to add the counter
        // to the purger. Once ::invalidate() did its work, this will lead to
        // calls to ::setDirectly() within the counter and this in fact, will
        // lead back here as that calls our write callback.
        $this->purgers[$id]->setRuntimeMeasurement($measurement);
      }
    }
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
   * {@inheritdoc}
   */
  public function setPurgers(array $purgers) {
    $this->purgers = $purgers;
    $this->initializeCounters();
  }

}
