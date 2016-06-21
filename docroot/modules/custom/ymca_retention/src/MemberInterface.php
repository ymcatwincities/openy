<?php

namespace Drupal\ymca_retention;

use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Provides an interface defining a Member entity.
 *
 * @ingroup ymca_retention
 */
interface MemberInterface extends ContentEntityInterface {

  /**
   * Sets the email address of the user.
   *
   * @param string $mail
   *   The new email address of the user.
   *
   * @return \Drupal\ymca_retention\MemberInterface
   *   The called member entity.
   */
  public function setEmail($mail);

  /**
   * Returns the email address of the user.
   *
   * @return string
   *   The email address.
   */
  public function getEmail();

  /**
   * Returns the member id of the user.
   *
   * @return string
   *   The member id.
   */
  public function getMemberId();

  /**
   * Sets the member id for the user.
   *
   * @param string $member_id
   *   The member id.
   *
   * @return \Drupal\ymca_retention\MemberInterface
   *   The called member entity.
   */
  public function setMemberId($member_id);

  /**
   * Returns the points for the user.
   *
   * @return string
   *   Points.
   */
  public function getPoints();

  /**
   * Sets the points for the user.
   *
   * @param int $value
   *   Points.
   *
   * @return \Drupal\ymca_retention\MemberInterface
   *   The called member entity.
   */
  public function setPoints($value);

  /**
   * Returns user first name.
   *
   * @return string
   *   First name.
   */
  public function getFirstName();

  /**
   * Sets user first name.
   *
   * @param string $value
   *   First name.
   *
   * @return \Drupal\ymca_retention\MemberInterface
   *   The called member entity.
   */
  public function setFirstName($value);

  /**
   * Returns user last name.
   *
   * @return string
   *   Last name.
   */
  public function getLastName();

  /**
   * Sets user last name.
   *
   * @param string $value
   *   Last name.
   *
   * @return \Drupal\ymca_retention\MemberInterface
   *   The called member entity.
   */
  public function setLastName($value);

  /**
   * Returns user full name.
   *
   * @return string
   *   Full name.
   */
  public function getFullName();

  /**
   * Returns user Branch ID.
   *
   * @return string
   *   Branch ID.
   */
  public function getBranchId();

  /**
   * Sets user Branch ID.
   *
   * @param string $value
   *   Branch ID.
   *
   * @return \Drupal\ymca_retention\MemberInterface
   *   The called member entity.
   */
  public function setBranchId($value);

  /**
   * Returns user visits number.
   *
   * @return string
   *   Visits number.
   */
  public function getVisits();

  /**
   * Sets user visits number.
   *
   * @param string $value
   *   Visits number.
   *
   * @return \Drupal\ymca_retention\MemberInterface
   *   The called member entity.
   */
  public function setVisits($value);

}
