<?php

namespace Drupal\openy_campaign\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\node\NodeInterface;
use Drupal\openy_campaign\CampaignMenuServiceInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a campaign menu block.
 *
 * @Block(
 *   id = "campaign_menu_block",
 *   admin_label = @Translation("Campaign menu block"),
 *   category = @Translation("Paragraph Blocks")
 * )
 */
class CampaignMenuBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The Campaign menu service.
   *
   * @var \Drupal\openy_campaign\CampaignMenuServiceInterface
   */
  protected $campaignMenuService;

  /**
   * The current route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * Constructs a new CampaignMenuBlock.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param CampaignMenuServiceInterface $campaign_menu_service
   *   The Campaign menu service.
   * @param RouteMatchInterface $route_match
   *   The Campaign menu service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, CampaignMenuServiceInterface $campaign_menu_service, RouteMatchInterface $route_match) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->campaignMenuService = $campaign_menu_service;
    $this->routeMatch = $route_match;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('openy_campaign.campaign_menu_handler'),
      $container->get('current_route_match')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    // Extract node from route.
    if (!$node = $this->routeMatch->getParameter('node')) {
      return [];
    }

    // There is no campaign associated with the node or empty campaign menu.
    if (!$links = $this->campaignMenuService->getNodeCampaignMenu($node)) {
      return [];
    }

    return [
      '#theme' => 'openy_campaign_campaign_menu',
      '#links' => $links,
      '#cache' => [
        'max-age' => 0,
      ],
    ];
  }

}
