<?php

namespace Drupal\openy_campaign\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\node\NodeInterface;

/**
 * Class GameController.
 */
class GameController extends ControllerBase {

  //static $gamesList = ['magic_ball', 'scratchcard', 'flip_cards'];

  static $gamesList = ['flip_cards'];
  /**
   * The entity repository.
   *
   * @var \Drupal\Core\Entity\EntityRepositoryInterface
   */
  protected $entityRepository;

  /**
   * Constructs a new GameController.
   *
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository
   *   The entity repository.
   */
  public function __construct(EntityRepositoryInterface $entity_repository) {
    $this->entityRepository = $entity_repository;
  }

  /**
   * {@inheritdoc}
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The Drupal service container.
   *
   * @return static
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.repository')
    );
  }

  /**
   * Play the Game page.
   */
  public function playGamePage($uuid) {
    /** @var \Drupal\openy_campaign\Entity\MemberGame $game */
    $game = $this->entityRepository->loadEntityByUuid('openy_campaign_member_game', $uuid);

    $result = $game->result->value;
    /** @var \Drupal\Node\Entity\Node $campaign */
    $campaign = $game->member->entity->campaign->entity;

    $link = Link::fromTextAndUrl(t('Back to Campaign'), new Url('entity.node.canonical', [ 'node' => $campaign->id()]))->toString();

    if (!empty($result)) {
      return [
        '#markup' => $link . ' You have played this chance already. Your result: ' . $result,
      ];
    }

    $coverImagePath = NULL;
    if (!empty($campaign->field_flip_cards_cover_image->entity)) {
      /** @var \Drupal\file\Entity\File $coverImage */
      $coverImage = $campaign->field_flip_cards_cover_image->entity;
      $coverImagePath = \Drupal::service('stream_wrapper_manager')->getViaUri($coverImage->getFileUri())->getExternalUrl();
    }
    else {
      $coverImagePath = base_path() . \Drupal::theme()->getActiveTheme()->getPath() . '/img/instant_game_cover_1.png';
    }

    $title = $campaign->field_campaign_game_title->value;
    $description = $campaign->field_campaign_game_description->value;

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

    $isWinner = FALSE;
    $randomNumber = mt_rand(0, $expected);
    $result = $campaign->field_campaign_prize_nowin->value;
    foreach ($ranges as $range) {
      if ($randomNumber >= $range['min'] && $randomNumber < $range['max']) {
        $result = $range['description'];
        $isWinner = TRUE;
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
    $game->date = \Drupal::time()->getRequestTime();
    $game->log->value = $logMessage;
    $game->save();

    // Select random game type from the list.
    $gameType = self::$gamesList[array_rand(self::$gamesList)];

    // Output different messages.
    // Get default values from settings
    $config = \Drupal::config('openy_campaign.general_settings');

    $messageNumber = mt_rand(1, 5);
    $msgLoose = $config->get('instant_game_loose_message_' . $messageNumber);
    $msgLoose = check_markup($msgLoose['value'], $msgLoose['format']);

    $messageNumber = mt_rand(1, 5);
    $msgWin = $config->get('instant_game_win_message_' . $messageNumber);
    $msgWin = check_markup($msgWin['value'], $msgWin['format']);
    $msgWin = str_replace('[game:result]', $result, $msgWin);


    return [
      '#theme' => 'openy_campaign_game_' . $gameType,
      '#result' => $result,
      '#link' => $link,
      '#title' => $title,
      '#description' => $description,
      '#coverImagePath' => $coverImagePath,
      '#msgWin' => $msgWin,
      '#msgLoose' => $msgLoose,
      '#isWinner' => $isWinner,
      '#attached' => [
        'library' => [
          'openy_campaign/game_' . $gameType,
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
   * @param \Drupal\node\NodeInterface $node
   *
   * @return array Render array
   */
  public function gameResults(NodeInterface $node) {
    $build = [
      'view' => views_embed_view('campaign_game_results', 'default', $node->id()),
    ];

    return $build;
  }

}
