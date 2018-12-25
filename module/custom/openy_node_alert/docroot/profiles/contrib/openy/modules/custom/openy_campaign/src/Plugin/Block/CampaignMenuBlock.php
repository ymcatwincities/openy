<?php

namespace Drupal\openy_campaign\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\node\Entity\Node;
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
   * Constructs a new CampaignMenuBlock.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\openy_campaign\CampaignMenuServiceInterface $campaign_menu_service
   *   The Campaign menu service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, CampaignMenuServiceInterface $campaign_menu_service) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->campaignMenuService = $campaign_menu_service;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('openy_campaign.campaign_menu_handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $noCache = ['#cache' => ['max-age' => CAMPAIGN_CACHE_TIME]];
    // Extract Campaign node from route.
    $campaign = $this->campaignMenuService->getCampaignNodeFromRoute();

    if ($campaign instanceof Node !== TRUE) {
      return $noCache;
    }

    // There is no campaign associated with the node or empty campaign menu.
    if (!$links = $this->campaignMenuService->getNodeCampaignMenu($campaign)) {
      return $noCache;
    }

    return [
      '#theme' => 'openy_campaign_campaign_menu',
      '#links' => $links,
    ] + $noCache;
  }

}
