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
   * @param string $member_id
   *   The member id.
   *
   * @return \Drupal\openy_campaign\MemberInterface
   *   The called member entity.
   */
  public function setMemberId($member_id);

  /**
   * Returns the personify id(MasterCustomerId) of the user.
   *
   * @return string
   *   The personify id.
   */
  public function getPersonifyId();

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
   * @return \Drupal\openy_campaign\MemberInterface
   *   The called member entity.
   */
  public function setVisits($value);

  /**
   * Return status, is user employee or not.
   *
   * @return bool
   *   Status.
   */
  public function isMemberEmployee();

  /**
   * Return status, is user created by staff.
   *
   * @return bool
   *   Status.
   */
  public function isCreatedByStaff();

  /**
   * Return status, is user created via mobile app.
   *
   * @return bool
   *   Status.
   */
  public function isCreatedOnMobile();

  /**
   * Returns user visit goal.
   *
   * @return int
   *   Visit goal.
   */
  public function getVisitGoal();

  /**
   * Sets user visit goal.
   *
   * @param string $value
   *   Value.
   *
   * @return \Drupal\openy_campaign\MemberInterface
   *   The called member entity.
   */
  public function setVisitGoal($value);

  /**
   * Calculate visit goal.
   *
   * @param array $member_ids
   *   Array of Master Customer IDs.
   *
   * @return array
   *   Visit goals for members keyed by Master Customer ID.
   */
  public static function calculateVisitGoal($member_ids);

}
