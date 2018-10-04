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

  public function isUnplayedGamesExist(NodeInterface $campaign) {
    if (!empty($this->getUnplayedGames($campaign))) {
      return TRUE;
    }
    return FALSE;
  }

  public function getUnplayedGames(NodeInterface $campaign) {

    $userData = MemberCampaign::getMemberCampaignData($campaign->id());
    $memberCampaignID = MemberCampaign::findMemberCampaign($userData['membership_id'], $campaign->id());

    $query = $this->entityTypeManager->getStorage('openy_campaign_member_game')->getQuery();
    $gameIds = $query->condition('member', $memberCampaignID)
      ->execute();

    $games = MemberGame::loadMultiple($gameIds);
    $unplayedGames = [];
    foreach ($games as $game) {
      if (!empty($game->result->value)) {
        continue;
      }

      $unplayedGames[] = $game;
    }
    return $unplayedGames;
  }
}
