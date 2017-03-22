<?php

namespace Drupal\ymca_retention;

use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Provides an interface defining a Winner entity.
 *
 * @ingroup ymca_retention
 */
interface WinnerInterface extends ContentEntityInterface {

  /**
   * Returns the member id.
   *
   * @return string
   *   The member id.
   */
  public function getMemberId();

  /**
   * Returns the winner branch id.
   *
   * @return string
   *   The member branch id.
   */
  public function getBranchId();

  /**
   * Returns the place.
   *
   * @return string
   *   The winner place.
   */
  public function getPlace();

}
