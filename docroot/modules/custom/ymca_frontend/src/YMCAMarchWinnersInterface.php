<?php

namespace Drupal\ymca_frontend;

/**
 * Interface YMCAMarchWinnersInterface.
 */
interface YMCAMarchWinnersInterface {

  /**
   * Get march winners.
   *
   * @return array
   *   List of winners.
   */
  public function getWinners();

}
