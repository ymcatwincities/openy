<?php

namespace Drupal\openy_campaign;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\node\NodeInterface;
use Drupal\openy_campaign\Entity\MemberCampaign;

/**
 * Provides an interface defining a Campaign Utilization Activity entity.
 *
 * @ingroup openy_campaign
 */
interface CampaignUtilizationActivityInterface extends ContentEntityInterface {

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
  public function getMemberCampaign();

  /**
   * Sets the campaign of the user.
   *
   * @param \Drupal\openy_campaign\Entity\MemberCampaign $memberCampaign
   *   The campaign object.
   *
   * @return \Drupal\openy_campaign\MemberCampaignInterface
   *   The called member entity.
   */
  public function setMemberCampaign(MemberCampaign $memberCampaign);

  /**
   * @param $created
   *
   * @return mixed
   */
  public function setCreated($created);

  /**
   * @return mixed
   */
  public function getCreated();

  /**
   * @return mixed
   */
  public function getActivityType();

  /**
   * @param $activityType
   *
   * @return mixed
   */
  public function setActivityType($activityType);

}
