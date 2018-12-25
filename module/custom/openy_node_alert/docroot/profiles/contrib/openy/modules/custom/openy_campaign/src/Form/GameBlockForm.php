<?php

namespace Drupal\openy_campaign\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StreamWrapper\StreamWrapperManagerInterface;
use Drupal\Core\Theme\ThemeManagerInterface;
use Drupal\openy_campaign\CampaignMenuService;
use Drupal\openy_campaign\GameService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a "openy_campaign_game_block_form" form.
 */
class GameBlockForm extends FormBase {

  /**
   * @var \Drupal\openy_campaign\CampaignMenuService
   */
  protected $campaignMenuService;

  /**
   * @var \Drupal\openy_campaign\GameService
   */
  protected $gameService;

  /**
   * @var \Drupal\Core\StreamWrapper\StreamWrapperManagerInterface
   */
  protected $streamWrapperManager;

  /**
   * @var \Drupal\Core\Theme\ThemeManagerInterface
   */
  protected $themeManager;

  /**
   * GameBlockForm constructor.
   *
   * @param \Drupal\openy_campaign\CampaignMenuService $campaignMenuService
   * @param \Drupal\openy_campaign\GameService $gameService
   * @param \Drupal\Core\StreamWrapper\StreamWrapperManagerInterface $streamWrapperManager
   * @param \Drupal\Core\Theme\ThemeManagerInterface $themeManager
   */
  public function __construct(
    CampaignMenuService $campaignMenuService,
    GameService $gameService,
    StreamWrapperManagerInterface $streamWrapperManager,
    ThemeManagerInterface $themeManager
  ) {
    $this->campaignMenuService = $campaignMenuService;
    $this->gameService = $gameService;
    $this->streamWrapperManager = $streamWrapperManager;
    $this->themeManager = $themeManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('openy_campaign.campaign_menu_handler'),
      $container->get('openy_campaign.game_service'),
      $container->get('stream_wrapper_manager'),
      $container->get('theme.manager')
    );
  }


  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'openy_campaign_game_block_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $unplayedGames = NULL) {
    // Get default values from settings.
    $config = $this->config('openy_campaign.general_settings');;

    $msgGameNoGames = $config->get('track_activity_game_no_games');
    $msgGameNoGames = check_markup($msgGameNoGames['value'], $msgGameNoGames['format']);

    $msgGameAlreadyWinner = $config->get('track_activity_game_already_winner');
    $msgGameAlreadyWinner = check_markup($msgGameAlreadyWinner['value'], $msgGameAlreadyWinner['format']);

    $msgGameRemainingOne = $config->get('track_activity_game_games_remaining_one');
    $msgGameRemainingOne = check_markup($msgGameRemainingOne['value'], $msgGameRemainingOne['format']);

    $msgGameRemainingMultiple = $config->get('track_activity_game_games_remaining_multiple');
    $msgGameRemainingMultiple = check_markup($msgGameRemainingMultiple['value'], $msgGameRemainingMultiple['format']);

    /** @var \Drupal\Node\Entity\Node $campaign */
    $campaign = $this->campaignMenuService->getCampaignNodeFromRoute();
    $isAllowedToPlay = TRUE;
    if ($campaign->field_campaign_game_one_time_win->value == 1 &&
      $this->gameService->isMemberWinner($campaign)) {
      $isAllowedToPlay = FALSE;
    }

    if (!$isAllowedToPlay) {
      return [
        'message' => [
          '#markup' => $msgGameAlreadyWinner,
        ],
      ];
    }

    if (empty($unplayedGames)) {
      return [
        'message' => [
          '#markup' => $msgGameNoGames,
        ],
      ];
    }

    $form['games'] = [
      '#type' => 'value',
      '#value' => $unplayedGames,
    ];


    $coverImagePath = NULL;
    if (!empty($campaign->field_flip_cards_cover_image->entity)) {
      /** @var \Drupal\file\Entity\File $coverImage */
      $coverImage = $campaign->field_flip_cards_cover_image->entity;
      $coverImagePath = $this->streamWrapperManager->getViaUri($coverImage->getFileUri())->getExternalUrl();
    }
    else {
      $coverImagePath = base_path() . $this->themeManager->getActiveTheme()->getPath() . '/img/instant_game_cover_1.png';
    }

    $form['label'] = [
      '#markup' => $this->formatPlural(count($unplayedGames), $msgGameRemainingOne, $msgGameRemainingMultiple),
    ];

    $form['cover_image'] = [
      '#markup' => $coverImagePath,
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Play instant-win game now!'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $games = $form_state->getValue('games');
    $game = array_shift($games);

    /** @var \Drupal\Node\Entity\Node $campaign */
    $campaign = $game->member->entity->campaign->entity;

    $form_state->setRedirect('openy_campaign.campaign_game', ['uuid' => $game->uuid()], [
      'query' => ['campaign_id' => $campaign->id()]
    ]);
  }

}
