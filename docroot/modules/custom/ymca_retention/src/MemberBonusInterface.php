<?php

namespace Drupal\ymca_retention;

use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Provides an interface defining a Member Bonus entity.
 *
 * @ingroup ymca_retention
 */
interface MemberBonusInterface extends ContentEntityInterface {

  /**
   * Returns the member id.
   *
   * @return int
   *   Member id.
   */
  public function getMember();

  /**
   * Returns the timestamp of the day when bonus was claimed.
   *
   * @return int
   *   Timestamp.
   */
  public function getDate();

  /**
   * Returns the bonus code.
   *
   * @return string
   *   Bonus code.
   */
  public function getBonusCode();

}
