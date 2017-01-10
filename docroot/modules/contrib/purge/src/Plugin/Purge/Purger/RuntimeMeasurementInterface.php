<?php

namespace Drupal\purge\Plugin\Purge\Purger;

use Drupal\purge\Counter\PersistentCounterInterface;

/**
 * Describes a execution time measurer for invalidation processing.
 */
interface RuntimeMeasurementInterface extends PersistentCounterInterface {

  /**
   * Return a value safe for time hints, between 0.1 and 10.00.
   *
   * @param float $value
   *   The measurement value.
   *
   * @see \Drupal\purge\Plugin\Purge\Purger\PurgerCapacityDataInterface::getTimeHint()
   *
   * @return float
   *   The same value or 0.1 or 10.0 when it exceeded either boundary.
   */
  public function getSafeTimeHintValue($value);

  /**
   * Start measuring execution time.
   *
   * @throws \LogicException
   *   Thrown when already started before without calling ::stop().
   *
   * @return void
   */
  public function start();

  /**
   * Stop measuring execution time and store if necessary.
   *
   * To gather safe time hint measurements, the following rules apply:
   *
   *  - All invalidations MUST have ::SUCCEEDED, if any of them failed the
   *    measurement will not be saved as it is likely unrepresentative data.
   *
   *  - Measurements slower than previous records take priority. This means that
   *    a single slow (yet successful) performance will relentlessly adjust the
   *    measurement upwards, better safe...
   *
   *  - Every faster measurement than previously stored records leads to 10%
   *    reduction of the last recorded measurement. This means structural low
   *    performance will be rewarded by more capacity, but slow and carefully!
   *
   * @param \Drupal\purge\Plugin\Purge\Invalidation\InvalidationInterface[] $invalidations
   *   Non-associative array of processed invalidation objects.
   *
   * @throws \LogicException
   *   Thrown when the $invalidations parameter is empty.
   * @throws \LogicException
   *   Thrown when any invalidation isn't a InvalidationInterface instance.
   * @throws \LogicException
   *   Thrown when ::start() hasn't been called yet.
   *
   * @return void.
   */
  public function stop(array $invalidations);

}
