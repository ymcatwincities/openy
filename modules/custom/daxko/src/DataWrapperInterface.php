<?php

namespace Drupal\daxko;

/**
 * Interface DataWrapperInterface.
 */
interface DataWrapperInterface {

  /**
   * Return price matrix.
   *
   * @return array
   *   Price matrix.
   */
  public function getMembershipPriceMatrix();

  /**
   * Return location pins.
   *
   * @return array
   *   Location pins.
   */
  public function getBranchPins();

}
