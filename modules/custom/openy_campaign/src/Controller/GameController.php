<?php

namespace Drupal\openy_campaign\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\node\NodeInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Class GameController.
 */
class GameController extends ControllerBase {

  /**
   * Play the Game page.
   */
  public function playGamePage($uuid) {
    $game = \Drupal::entityManager()->loadEntityByUuid('openy_campaign_member_game', $uuid);

    $result = $game->result->value;
    $campaign = $game->member->entity->campaign->entity;

    $link = \Drupal::l('Back to Campaign', new Url('entity.node.canonical', [ 'node' => $campaign->id()]));

    if (!empty($result)) {
      return [
        '#markup' => $link . ' You have played this chance already. Your result: ' . $result,
      ];
    }

    $expected = $campaign->field_campaign_expected_visits->value;
    $coefficient = $campaign->field_campaign_prize_coefficient->value;
    $ranges = [];

    $previousRange = 0;
    foreach ($campaign->field_campaign_prizes as $target) {
      $prize = $target->entity;
      $nextRange = $previousRange + ceil($prize->field_prgf_prize_amount->value * $coefficient);

      $ranges[] = [
        'min' => $previousRange,
        'max' => $nextRange,
        'description' => $prize->field_prgf_prize_description->value,
      ];

      $previousRange = $nextRange;
    }

    $randomNumber = rand(0, $expected);

    $result = $campaign->field_campaign_prize_nowin->value;
    foreach ($ranges as $range) {
      if ($randomNumber >= $range['min'] && $randomNumber < $range['max']) {
        $result = $range['description'];
        break;
      }
    }

    $logMessage = json_encode([
      'randomNumber' => $randomNumber,
      'expected' => $expected,
      'coefficient' => $coefficient,
      'ranges' => $ranges,
    ]);

    $game->result->value = $result;
    $game->date = REQUEST_TIME;
    $game->log->value = $logMessage;
    $game->save();

    $html = '<div class="shadow"></div>
    <div class="epos">
    <div class="eball">
      <div class="egrad"></div>
      <div class="ewin"><div>
      <div class="triangle"></div>
      <div class="textbox"></div>
    </div>
    </div>';

    return [
      '#markup' => $link . $html,
      '#attached' => [
        'library' => [
          'openy_campaign/magic_ball',
        ],
        'drupalSettings' => [
          'openy_campaign' => [
            'result' => $result,
          ],
        ],
      ]
    ];
  }


  /**
   * Checks access for a specific request.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Run access checks for this account.
   */
  public function campaignResultsAccess(NodeInterface $node, AccountInterface $account) {
    return AccessResult::allowedIf($account->hasPermission('administer retention campaign') && $node->getType() == 'campaign');
  }

  public function campaignResults(NodeInterface $node) {
    $build = [
      'view' => views_embed_view('campaign_results', 'default', $node->id()),
    ];

    return $build;
  }

}
