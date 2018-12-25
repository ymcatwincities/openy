<?php

namespace Drupal\openy_campaign;

use Drupal\node\NodeInterface;
use Drupal\openy_campaign\Entity\MemberCampaign;
use Drupal\openy_campaign\Entity\MemberGame;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Class GameService.
 *
 * @package Drupal\openy_campaign
 */
class GameService {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * Check if the user has more game chances in the selected campaign.
   *
   * @param \Drupal\node\NodeInterface $campaign
   *
   * @return bool
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  public function isUnplayedGamesExist(NodeInterface $campaign) {
    if (!empty($this->getUnplayedGames($campaign))) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Get the list of available game chances.
   *
   * @param \Drupal\node\NodeInterface $campaign
   *
   * @return array
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  public function getUnplayedGames(NodeInterface $campaign) {

    $userData = MemberCampaign::getMemberCampaignData($campaign->id());
    $memberCampaignID = MemberCampaign::findMemberCampaign($userData['membership_id'], $campaign->id());

    $memberGameStorage = $this->entityTypeManager->getStorage('openy_campaign_member_game');

    $query = $memberGameStorage->getQuery();
    $gameIds = $query->condition('member', $memberCampaignID)
      ->execute();

    $games = $memberGameStorage->loadMultiple($gameIds);
    $unplayedGames = [];
    foreach ($games as $game) {
      if (!empty($game->result->value)) {
        continue;
      }

      $unplayedGames[] = $game;
    }
    return $unplayedGames;
  }

  /**
   * Determines if the member was a winner at least once.
   *
   * @param \Drupal\node\NodeInterface $campaign
   *
   * @return bool
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  public function isMemberWinner(NodeInterface $campaign) {
    $isWinner = FALSE;

    $looseString = $campaign->field_campaign_prize_nowin->value;
    $userData = MemberCampaign::getMemberCampaignData($campaign->id());
    $memberCampaignID = MemberCampaign::findMemberCampaign($userData['membership_id'], $campaign->id());

    $memberGameStorage = $this->entityTypeManager->getStorage('openy_campaign_member_game');
    $query = $memberGameStorage->getQuery();
    $gameIds = $query->condition('member', $memberCampaignID)
      ->execute();

    $games = $memberGameStorage->loadMultiple($gameIds);
    foreach ($games as $game) {
      if (!empty($game->result->value) && $game->result->value != $looseString) {
        $isWinner = TRUE;
      }
    }

    return $isWinner;
  }
}
