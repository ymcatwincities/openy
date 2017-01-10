<?php

namespace Drupal\purge\Plugin\Purge\Purger;

use Drupal\Core\DestructableInterface;

/**
 * Describes a tracker that tracks RuntimeMeasurement objects for purgers.
 *
 * This tracker creates RuntimeMeasurement counters for purgers that return TRUE
 * on their ::hasRuntimeMeasurement() implementation. When the counter objects
 * attempt to store a new measurement value, this tracker will store the
 * values for all counters in the underlying storing mechanism.
 */
interface RuntimeMeasurementTrackerInterface extends DestructableInterface {

  /**
   * Set all purger plugin instances.
   *
   * @param \Drupal\purge\Plugin\Purge\Purger\PurgerInterface[] $purgers
   *   All purger plugins instantiated by \Drupal\purge\Plugin\Purge\Purger\PurgersServiceInterface.
   */
  public function setPurgers(array $purgers);

}
