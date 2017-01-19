<?php

namespace Drupal\openy_calc;

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

}
