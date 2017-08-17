<?php

namespace Drupal\openy_prgf_campaign_menu\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\node\NodeInterface;
use Drupal\openy_prgf_campaign_menu\CampaignMenuServiceInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a campaign menu block.
 *
 * @Block(
 *   id = "campaign_menu",
 *   admin_label = @Translation("Campaign menu block"),
 *   category = @Translation("Paragraph Blocks")
 * )
 */
class CampaignMenuBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The Campaign menu service.
   *
   * @var \Drupal\openy_prgf_campaign_menu\CampaignMenuServiceInterface
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
      $container->get('openy_prgf_campaign_menu.menu_handler'),
      $container->get('current_route_match')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    return array(
      '#markup' => $this->t('Generate Campaign menu.'),
    );
    // Extract node from route.
    if (!$node = $this->routeMatch->getParameter('node')) {
      return [];
    }

    // There is no campaign associated with the node or empty campaign menu.
    if (!$links = $this->campaignMenuService->getNodeCampaignMenu($node)) {
      return [];
    }

    /* @var NodeInterface $campaign */
    $campaign = $this->campaignMenuService->getNodeCampaignNode($node);
    $tags = $campaign->getCacheTags();
    if ($node != $campaign) {
      $tags_campaign = $node->getCacheTags();
      $tags = Cache::mergeTags($tags, $tags_campaign);
    }

    $current_route = $this->routeMatch->getCurrentRouteMatch();
    $current_route_url = Url::fromRoute($current_route->getRouteName(), $current_route->getRawParameters()->all());
    $current_internal_path = $current_route_url->getInternalPath();

    // Add default home link to the campaign node.
    array_unshift($links, [
      'uri' => 'entity:' . $campaign->getEntityTypeId() . '/' . $campaign->id(),
      'title' => t('Home'),
      'options' => [],
    ]);

    foreach ($links as &$link) {
      $url = Url::fromUri($link['uri']);
      $link = (new Link($link['title'], $url))->toRenderable();
      // If link is to current page set 'active' class.
      if (!$url->isExternal() && $url->isRouted() && $current_internal_path == $url->getInternalPath()) {
        $link['#attributes']['class'] = ['active'];
      }
    }

    return [
      '#theme' => 'campaign_menu',
      '#links' => $links,
      '#cache' => [
        'tags' => Cache::mergeTags(['campaign_menu'], $tags),
      ],
    ];
  }

}
