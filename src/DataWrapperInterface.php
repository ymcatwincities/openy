<?php

namespace Drupal\openy_calc;

/**
 * Interface DataWrapperInterface.
 */
interface DataWrapperInterface {

  /**
   * Get list of membership types.
   *
   * @return array
   *   The list of membership types keyed by type ID.
   */
  public function getMembershipTypes();

  /**
   * Get the list of locations.
   *
   * @return array
   *   The list of locations keyed by location ID.
   */
  public function getLocations();

  /**
   * Get price.
   *
   * @param int $location_id
   *   Location ID.
   * @param string $membership_type
   *   Membership type ID.
   *
   * @return string
   *   Price.
   */
  public function getPrice($location_id, $membership_type);

}
