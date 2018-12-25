<?php

namespace Drupal\purge\Plugin\Purge\Purger;

use Drupal\purge\Plugin\Purge\Invalidation\InvalidationInterface;
use Drupal\purge\Plugin\Purge\Purger\RuntimeMeasurementInterface;
use Drupal\purge\Counter\PersistentCounter;

/**
 * Provides a execution time measurer for invalidation processing.
 */
class RuntimeMeasurement extends PersistentCounter implements RuntimeMeasurementInterface {

  /**
   * The initial time measurement.
   *
   * @var null|float
   */
  protected $start = NULL;

  /**
   * {@inheritdoc}
   */
  public function getSafeTimeHintValue($value) {
    if ($value < 0.1) {
      return 0.1;
    }
    elseif ($value > 10.0) {
      return 10.0;
    }
    return $value;
  }

  /**
   * {@inheritdoc}
   */
  public function start() {
    if (!is_null($this->start)) {
      throw new \LogicException("Already started, call ->stop() first!");
    }
    $this->start = microtime(TRUE);
  }

  /**
   * {@inheritdoc}
   */
  public function stop(array $invalidations) {
    if (empty($invalidations)) {
      throw new \LogicException('The $invalidations parameter is empty.');
    }
    if (is_null($this->start)) {
      throw new \LogicException("Not yet started, call ->start first!");
    }

    // Check if any of the invalidations failed, if so, stop.
    foreach ($invalidations as $invalidation) {
      if (!$invalidation instanceof InvalidationInterface) {
        throw new \LogicException('One of the $invalidations is not a InvalidationInterface derivative!');
      }
      if ($invalidation->getState() !== InvalidationInterface::SUCCEEDED) {
        return;
      }
    }

    // Calculate the spent execution time per invalidation by dividing it
    // through the number of invalidations processed. We're also adding 15% of
    // time for theoretic overhead and ensure that the final value remains
    // within the boundaries of ::getTimeHint().
    if (($spent = microtime(TRUE) - $this->start) === 0.0) {
      return;
    }
    $spent = $this->getSafeTimeHintValue(
      ($spent / count($invalidations)) * 1.15
    );

    // Immediately write fresh or slower measurements.
    if (($this->value === 0.0) || ($spent > $this->value)) {
      $this->set($spent);
    }

    // Slowly adapt to faster measurements by lowering by 10%.
    elseif ($spent < $this->value) {
      $slow_adjustment = $this->getSafeTimeHintValue($this->value * 0.9);
      if ($slow_adjustment >= $spent) {
        $this->set($slow_adjustment);
      }
    }

    // Reset the start value so that new measurements can happen.
    $this->start = NULL;
  }

}
