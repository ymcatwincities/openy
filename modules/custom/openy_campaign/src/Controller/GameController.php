<?php

namespace Drupal\openy_campaign\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\openy_campaign\Entity\MemberCampaign;
use Drupal\openy_campaign\Entity\MemberGame;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\OpenModalDialogCommand;
use Drupal\Core\Ajax\RedirectCommand;
use Drupal\Core\Form\FormBuilder;
use Drupal\Core\Url;

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

    $link = \Drupal::l('Back to Campaign', new Url('entity.node.canonical', [ 'node' => $game->member->entity->campaign->entity->id()]));

    if (!empty($result)) {
      return [
        '#markup' => $link . ' You have played this chance already. Your result: ' . $result,
      ];
    }

    $options = [
      'Try next time',
      'You won towel',
      'You won free membership',
    ];

    $key = rand(0, count($options) - 1);

    $result = $options[$key];

    $game->result->value = $result;
    $game->date = REQUEST_TIME;
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
}
