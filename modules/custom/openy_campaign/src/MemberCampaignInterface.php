<?php

namespace Drupal\openy_campaign;

use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Provides an interface defining a MemberCampaign entity.
 *
 * @ingroup openy_campaign
 */
interface MemberCampaignInterface extends ContentEntityInterface {

  /**
   * Get MemberCampaign id.
   *
   * @return int
   *   Internal Id.
   */
  public function getId();

  /**
   * Returns the campaign id of the user.
   *
   * @return int
   *   campaign Id.
   */
  public function getCampaignId();

  /**
   * Sets the campaign id of the user.
   *
   * @param int $campaign_id
   *   The campaign id of the user.
   *
   * @return \Drupal\openy_campaign\MemberCampaignInterface
   *   The called member entity.
   */
  public function setCampaignId($campaign_id);

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

}
