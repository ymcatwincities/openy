<?php

namespace Drupal\openy_campaign\Plugin\Block;

use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Block\BlockBase;
use Drupal\openy_campaign\Entity\MemberCampaign;
use Drupal\openy_campaign\Entity\MemberGame;
use Drupal\openy_campaign\GameService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\openy_campaign\CampaignMenuServiceInterface;

/**
 * Provides a 'Campaign Game' block where members can play the game.
 *
 * @Block(
 *   id = "campaign_game_block",
 *   admin_label = @Translation("Campaign Game Block"),
 *   category = @Translation("Paragraph Blocks")
 * )
 */
class CampaignGameBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * Form builder.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * The service container.
   *
   * @var \Symfony\Component\DependencyInjection\ContainerInterface
   */
  protected $container;

  /**
   * The Campaign menu service.
   *
   * @var \Drupal\openy_campaign\CampaignMenuServiceInterface
   */
  protected $campaignMenuService;

  /**
   * The Game service.
   *
   * @var \Drupal\openy_campaign\GameService
   */
  protected $game_service;

  /**
   * Constructs a new Block instance.
   *
   * @param array $configuration
   *   The plugin configuration.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Form\FormBuilderInterface $formBuilder
   *   Form builder.
   */
  public function __construct(ContainerInterface $container,
    array $configuration, $plugin_id, $plugin_definition,
    FormBuilderInterface $formBuilder,
    CampaignMenuServiceInterface $campaign_menu_service,
    GameService $game_service
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->formBuilder = $formBuilder;
    $this->container = $container;
    $this->campaignMenuService = $campaign_menu_service;
    $this->game_service = $game_service;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $container,
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('form_builder'),
      $container->get('openy_campaign.campaign_menu_handler'),
      $container->get('openy_campaign.game_service')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $block = [];

    // The block is rendered for each user separately.
    // It should be invalidated when a new game chance is added.
    $block['#cache'] = [
      'max-age' => 3600,
    ];

    // Get campaign node from current page.
    /** @var \Drupal\Node\Entity\Node $campaign */
    $campaign = $this->campaignMenuService->getCampaignNodeFromRoute();

    if (empty($campaign)) {
      return $block;
    }

    $block['#campaign'] = $campaign;
    $block['#game_type'] = $campaign->field_campaign_game_type->value;
    $unplayedGames = $this->game_service->getUnplayedGames($campaign);
    $block['#theme'] = 'openy_campaign_games';
    $block['#form'] = $this->formBuilder->getForm('Drupal\openy_campaign\Form\GameBlockForm', $unplayedGames);
    return $block;
  }

}
