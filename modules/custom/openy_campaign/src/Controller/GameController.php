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

  /**
   * Possible games list: ['magic_ball', 'scratchcard', 'flip_cards', 'spin_the_wheel'].
   */

  static $gamesList = ['flip_cards', 'spin_the_wheel'];
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
  public function playGamePage($node) {

  }

  /**
   * Play one Game page.
   */
  public function playOneGamePage($uuid) {
    // Disable response caching.
    \Drupal::service('page_cache_kill_switch')->trigger();

    $gameResult = $this->generateGameResult($uuid);

    /** @var \Drupal\openy_campaign\Entity\MemberGame $game */
    $game = $this->entityRepository->loadEntityByUuid('openy_campaign_member_game', $uuid);

    $result = $game->result->value;
    /** @var \Drupal\Node\Entity\Node $campaign */
    $campaign = $game->member->entity->campaign->entity;

    /** @var \Drupal\openy_campaign\CampaignMenuService $campaignMenuService */
    $campaignMenuService = \Drupal::service('openy_campaign.campaign_menu_handler');
    $pallete = $campaignMenuService->getCampaignPalette($campaign);

    if (!empty($gameResult['already_used_chance'])) {
      $link = Link::fromTextAndUrl(t('Back to Campaign'), new Url('entity.node.canonical', ['node' => $campaign->id()]))->toString();
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

    $gameType = $campaign->field_campaign_game_type->value;
    if (!in_array($gameType, self::$gamesList)) {
      return;
    }

    $isWinner = $gameResult['is_winner'];
    $result = $gameResult['result'];

    // Output different messages.
    // Get default values from settings.
    $config = \Drupal::config('openy_campaign.general_settings');
    $messageNumber = mt_rand(1, 5);
    $message = $config->get('instant_game_' . ($isWinner ? 'win' : 'loose') . '_message_' . $messageNumber);

    $message = check_markup($message['value'], $message['format']);
    $messageTitle = $config->get('instant_game_' . ($isWinner ? 'win' : 'loose') . ' _title');
    $messageTitle = check_markup($messageTitle['value'], $messageTitle['format']);

    if ($isWinner) {
      $message = str_replace('[game:result]', $result, $message);
    }

    $isUnplayedGamesExist = \Drupal::service('openy_campaign.game_service')->isUnplayedGamesExist($campaign);

    $nextGame = NULL;
    $nextGameUrl = '';
    if ($isUnplayedGamesExist) {
      $unPlayedGames = \Drupal::service('openy_campaign.game_service')->getUnplayedGames($campaign);
      $nextGame = reset($unPlayedGames);
      $nextGameUrl = Link::fromTextAndUrl(t('Play again'), Url::fromRoute('openy_campaign.campaign_game', [
          'uuid' => $nextGame->uuid()
        ], [
          'query' => [
            'campaign_id' => $campaign->id()
          ],
          'attributes' => [
            'class' => [
              'btn'
            ]
          ]
        ]));
    } else {
      $activePage = $campaignMenuService->getActiveCampaignPage($campaign);
      $nextGameUrl = Link::fromTextAndUrl(t('Back to campaign'), Url::fromRoute('entity.node.canonical', [
        'node' => $activePage->id()
      ], [
        'attributes' => [
          'class' => [
            'btn'
          ]
        ]
      ]));
    }

    return [
      '#theme' => 'openy_campaign_game_' . $gameType,
      '#result' => $result,
      '#title' => $title,
      '#description' => $description,
      '#coverImagePath' => $coverImagePath,
      '#message' => $message,
      '#messageTitle' => $messageTitle,
      '#isWinner' => $isWinner,
      '#isUnplayedGamesExist' => $isUnplayedGamesExist,
      '#nextGameUrl' => $nextGameUrl,
      '#attached' => [
        'library' => [
          'openy_campaign/game_' . $gameType,
        ],
        'drupalSettings' => [
          'openy_campaign' => [
            'pallete' => $pallete['colors'],
            'result' => $result,
          ],
        ],
      ]
    ];
  }

  /**
   * Show list of all game results.
   *
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

  /**
   * Draw instant-win game result.
   *
   * @param $uuid
   *
   * @return array
   */
  public function generateGameResult($uuid) {
    /** @var \Drupal\openy_campaign\Entity\MemberGame $game */
    $game = $this->entityRepository->loadEntityByUuid('openy_campaign_member_game', $uuid);

    $result = $game->result->value;
    /** @var \Drupal\Node\Entity\Node $campaign */
    $campaign = $game->member->entity->campaign->entity;

    if (!empty($result)) {
      return [
        'already_used_chance' => TRUE,
      ];
    }

    $expected = $campaign->field_campaign_expected_visits->value;
    $coefficient = $campaign->field_campaign_prize_coefficient->value;
    $ranges = [];

    $previousRange = 0;
    foreach ($campaign->field_campaign_prizes as $target) {
      /** @var \Drupal\paragraphs\Entity\Paragraph $prize */
      $prize = $target->entity;
      $amount = $prize->field_prgf_prize_amount->value;
      if ($amount == 0) {
        continue;
      }
      $nextRange = $previousRange + ceil($prize->field_prgf_prize_amount->value * $coefficient);

      $ranges[] = [
        'min' => $previousRange,
        'max' => $nextRange,
        'description' => $prize->field_prgf_prize_description->value,
        'prize' => $prize,
      ];

      $previousRange = $nextRange;
    }

    $isWinner = FALSE;
    $randomNumber = mt_rand(0, $expected);
    $result = $campaign->field_campaign_prize_nowin->value;
    if (empty($result)) {
      $result = 'Did not win.';
    }
    foreach ($ranges as $range) {
      if ($randomNumber >= $range['min'] && $randomNumber < $range['max']) {
        $result = $range['description'];
        $isWinner = TRUE;

        // Decrease an amount of the prizes.
        $prize = $range['prize'];
        $amount = $prize->field_prgf_prize_amount->value;
        $prize->set('field_prgf_prize_amount', $amount - 1);
        $prize->save();

        break;
      }
    }

    $logMessage = json_encode([
      'number' => $randomNumber,
      'expected' => $expected,
      'coeff' => $coefficient,
      'ranges' => array_map(function ($item) {
        return $item['min'] . '-' . $item['max'] . ':' . substr($item['description'], 0, 10);
      }, $ranges),
    ]);

    $game->result->value = $result;
    $game->date = \Drupal::time()->getRequestTime();
    $game->log->value = $logMessage;
    $game->save();

    return [
      'is_winner' => $isWinner,
      'result' => $result,
    ];

  }

}
