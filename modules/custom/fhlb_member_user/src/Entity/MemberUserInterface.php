<?php

namespace Drupal\fhlb_member_user\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Member user entities.
 *
 * @ingroup fhlb_member_user
 */
interface MemberUserInterface extends ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface {

  /**
   * Gets the Member user creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Member user.
   */
  public function getCreatedTime();

  /**
   * Sets the Member user creation timestamp.
   *
   * @param int $timestamp
   *   The Member user creation timestamp.
   *
   * @return \Drupal\fhlb_member_user\Entity\MemberUserInterface
   *   The called Member user entity.
   */
  public function setCreatedTime($timestamp);

}
