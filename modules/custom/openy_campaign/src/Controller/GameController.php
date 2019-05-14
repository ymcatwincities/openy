<?php

namespace Drupal\openy_campaign\Controller;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\StreamWrapper\StreamWrapperManagerInterface;
use Drupal\Core\Theme\ThemeManagerInterface;
use Drupal\openy_campaign\CampaignMenuService;
use Drupal\openy_campaign\GameService;
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
   * @var \Drupal\openy_campaign\GameService
   */
  protected $gameService;

  /**
   * @var \Drupal\openy_campaign\CampaignMenuService
   */
  protected $campaignMenuService;

  /**
   * @var \Drupal\Core\StreamWrapper\StreamWrapperManagerInterface
   */
  protected $streamWrapperManager;

  /**
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * @var \Drupal\Core\Theme\ThemeManagerInterface
   */
  protected $themeManager;

  /**
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $time;

  /**
   * Constructs a new GameController.
   *
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entityRepository
   * @param \Drupal\openy_campaign\GameService $gameService
   * @param \Drupal\openy_campaign\CampaignMenuService $campaignMenuService
   * @param \Drupal\Core\StreamWrapper\StreamWrapperManagerInterface $streamWrapperManager
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   * @param \Drupal\Core\Theme\ThemeManagerInterface $themeManager
   * @param \Drupal\Component\Datetime\TimeInterface $time
   */
  public function __construct(
    EntityRepositoryInterface $entityRepository,
    GameService $gameService,
    CampaignMenuService $campaignMenuService,
    StreamWrapperManagerInterface $streamWrapperManager,
    ConfigFactoryInterface $configFactory,
    ThemeManagerInterface $themeManager,
    TimeInterface $time
  ) {
    $this->entityRepository = $entityRepository;
    $this->gameService = $gameService;
    $this->campaignMenuService = $campaignMenuService;
    $this->streamWrapperManager = $streamWrapperManager;
    $this->themeManager = $themeManager;
    $this->time = $time;

    $this->config = $configFactory->get('openy_campaign.general_settings');
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
      $container->get('entity.repository'),
      $container->get('openy_campaign.game_service'),
      $container->get('openy_campaign.campaign_menu_handler'),
      $container->get('stream_wrapper_manager'),
      $container->get('config.factory'),
      $container->get('theme.manager'),
      $container->get('datetime.time')
    );
  }

  /**
   * Play one Game page.
   */
  public function playOneGamePage($uuid) {
    $gameResult = $this->generateGameResult($uuid);

    /** @var \Drupal\openy_campaign\Entity\MemberGame $game */
    $game = $this->entityRepository->loadEntityByUuid('openy_campaign_member_game', $uuid);

    $result = $game->result->value;
    /** @var \Drupal\Node\Entity\Node $campaign */
    $campaign = $game->member->entity->campaign->entity;

    $pallete = $this->campaignMenuService->getCampaignPalette($campaign);

    if (!empty($gameResult['already_used_chance'])) {
      $link = Link::fromTextAndUrl($this->t('Back to Campaign'), new Url('entity.node.canonical', ['node' => $campaign->id()]))->toString();
      return [
        '#markup' => $link . ' ' . $this->t('You have played this chance already. Your result: @result.', ['@result' => $result]),
      ];
    }

    $coverImagePath = NULL;
    if (!empty($campaign->field_flip_cards_cover_image->entity)) {
      /** @var \Drupal\file\Entity\File $coverImage */
      $coverImage = $campaign->field_flip_cards_cover_image->entity;
      $coverImagePath = $this->streamWrapperManager->getViaUri($coverImage->getFileUri())->getExternalUrl();
    }
    else {
      $coverImagePath = base_path() . $this->themeManager->getActiveTheme()->getPath() . '/img/instant_game_cover_1.png';
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
    $messageNumber = mt_rand(1, 5);
    $message = $this->config->get('instant_game_' . ($isWinner ? 'win' : 'loose') . '_message_' . $messageNumber);

    $message = check_markup($message['value'], $message['format']);
    $messageTitle = $this->config->get('instant_game_' . ($isWinner ? 'win' : 'loose') . ' _title');
    $messageTitle = check_markup($messageTitle['value'], $messageTitle['format']);

    if ($isWinner) {
      $message = str_replace('[game:result]', $result, $message);
    }

    $isUnplayedGamesExist = $this->gameService->isUnplayedGamesExist($campaign);

    $isAllowedToPlay = TRUE;
    if ($campaign->field_campaign_game_one_time_win->value == 1 &&
      $this->gameService->isMemberWinner($campaign)) {
      $isAllowedToPlay = FALSE;
    }

    $nextGame = NULL;
    if ($isUnplayedGamesExist && $isAllowedToPlay) {
      $unPlayedGames = $this->gameService->getUnplayedGames($campaign);
      $nextGame = reset($unPlayedGames);
      $nextGameUrl = Link::fromTextAndUrl($this->t('Play again'), Url::fromRoute('openy_campaign.campaign_game', [
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
      $activePage = $this->campaignMenuService->getActiveCampaignPage($campaign);
      $nextGameUrl = Link::fromTextAndUrl($this->t('Back to campaign'), Url::fromRoute('entity.node.canonical', [
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
   * @throws \Drupal\Core\Entity\EntityStorageException
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
      $result = $this->t('Did not win.');
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
        //return $item['min'] . '-' . $item['max'] . ':' . substr($item['description'], 0, 10);
        return $item['min'] . '-' . $item['max'];
      }, $ranges),
    ]);

    $game->result->value = $result;
    $game->date = $this->time->getRequestTime();
    $game->log->value = $logMessage;
    $game->save();

    return [
      'is_winner' => $isWinner,
      'result' => $result,
    ];

  }

}
