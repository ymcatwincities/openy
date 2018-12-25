<?php

namespace Drupal\ymca_retention;

use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Provides an interface defining a Member Check-in entity.
 *
 * @ingroup ymca_retention
 */
interface MemberCheckInInterface extends ContentEntityInterface {

  /**
   * Returns the member id.
   *
   * @return int
   *   Member id.
   */
  public function getMember();

  /**
   * Returns the timestamp of the day when check-in was logged.
   *
   * @return int
   *   Timestamp.
   */
  public function getDate();

}
