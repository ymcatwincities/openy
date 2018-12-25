<?php

namespace Drupal\ymca_retention\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Class LeaderboardController.
 */
class LeaderboardController extends ControllerBase {

  /**
   * Return leaderboard for specified branch id.
   */
  public function leaderboardJson($branch_id) {
    /** @var \Drupal\ymca_retention\LeaderboardManager $service */
    $service = \Drupal::service('ymca_retention.leaderboard_manager');
    $leaderboard = $service->getLeaderboard($branch_id);

    $response = new JsonResponse($leaderboard);

    return $response;
  }

}
