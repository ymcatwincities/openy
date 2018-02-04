<?php

namespace Drupal\openy_campaign;

use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Provides an interface defining a Member entity.
 *
 * @ingroup openy_campaign
 */
interface MemberInterface extends ContentEntityInterface {

  /**
   * Get member id.
   *
   * @return int
   *   Internal Id.
   */
  public function getId();

  /**
   * Sets the email address of the user.
   *
   * @param string $mail
   *   The new email address of the user.
   *
   * @return \Drupal\openy_campaign\MemberInterface
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
   * Sets the email address of the user from Personify.
   *
   * @param string $email
   *   The new email address of the user.
   *
   * @return \Drupal\openy_campaign\MemberInterface
   *   The called member entity.
   */
  public function setPersonifyEmail($email);

  /**
   * Returns the email address of the user from Personify.
   *
   * @return string
   *   The email address.
   */
  public function getPersonifyEmail();

  /**
   * Returns the member id(FacilityCardNumber) of the user.
   *
   * @return string
   *   The member id.
   */
  public function getMemberId();

  /**
   * Sets the member id for the user.
   *
   * @param string $membership_id
   *   The member id.
   *
   * @return \Drupal\openy_campaign\MemberInterface
   *   The called member entity.
   */
  public function setMemberId($membership_id);

  /**
   * Returns the personify id (MasterCustomerId) of the user.
   *
   * @return string
   *   The personify id.
   */
  public function getPersonifyId();

  /**
   * Sets the personify id for the user.
   *
   * @param string $personify_id
   *   The member id.
   *
   * @return \Drupal\openy_campaign\MemberInterface
   *   The called member entity.
   */
  public function setPersonifyId($personify_id);

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
   * @return \Drupal\openy_campaign\MemberInterface
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
   * @return \Drupal\openy_campaign\MemberInterface
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
   * Return status, is user employee or not.
   *
   * @return bool
   *   Status.
   */
  public function isMemberEmployee();

  /**
   * Returns user birthday.
   *
   * @return string
   *   Birthday.
   */
  public function getBirthDate();

  /**
   * Sets user birthday.
   *
   * @return \Drupal\openy_campaign\MemberInterface
   *   The called member entity.
   */
  public function setBirthDate($value);

  /**
   * Returns user Payment type.
   *
   * @return string
   *   Payment type.
   */
  public function getPaymentType();

  /**
   * Set user Payment type.
   *
   * @return \Drupal\openy_campaign\MemberInterface
   *   The called member entity.
   */
  public function setPaymentType($value);

  /**
   * Returns user Member unit type.
   *
   * @return string
   *   Member unit type.
   */
  public function getMemberUnitType();

  /**
   * Set user Member unit type.
   *
   * @return \Drupal\openy_campaign\MemberInterface
   *   The called member entity.
   */
  public function setMemberUnitType($value);

}
