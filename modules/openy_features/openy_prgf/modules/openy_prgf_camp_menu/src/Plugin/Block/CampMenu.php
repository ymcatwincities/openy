<?php

namespace Drupal\openy_prgf_camp_menu\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\node\NodeInterface;
use Drupal\openy_prgf_camp_menu\CampMenuServiceInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a leader board block.
 *
 * @Block(
 *   id = "camp_menu",
 *   admin_label = @Translation("Camp menu block"),
 *   category = @Translation("Paragraph Blocks")
 * )
 */
class CampMenu extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The Camp menu service.
   *
   * @var \Drupal\openy_prgf_camp_menu\CampMenuServiceInterface
   */
  protected $campMenuService;

  /**
   * The current route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * Constructs a new CampMenu.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param CampMenuServiceInterface $camp_menu_service
   *   The Camp menu service.
   * @param RouteMatchInterface $route_match
   *   The Camp menu service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, CampMenuServiceInterface $camp_menu_service, RouteMatchInterface $route_match) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->campMenuService = $camp_menu_service;
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
      $container->get('openy_prgf_camp_menu.menu_handler'),
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

    // There is no camp associated with the node or empty camp menu.
    if (!$links = $this->campMenuService->getNodeCampMenu($node)) {
      return [];
    }

    /* @var NodeInterface $camp */
    $camp = $this->campMenuService->getNodeCampNode($node);
    $tags = $camp->getCacheTags();
    if ($node != $camp) {
      $tags_camp = $node->getCacheTags();
      $tags = Cache::mergeTags($tags, $tags_camp);
    }

    $current_route = $this->routeMatch->getCurrentRouteMatch();
    $current_route_url = Url::fromRoute($current_route->getRouteName(), $current_route->getRawParameters()->all());
    $current_internal_path = $current_route_url->getInternalPath();

    // Add default home link to the camp node.
    array_unshift($links, [
      'uri' => 'entity:' . $camp->getEntityTypeId() . '/' . $camp->id(),
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
      '#theme' => 'camp_menu',
      '#links' => $links,
      '#cache' => [
        'tags' => Cache::mergeTags(['camp_menu'], $tags),
      ],
    ];
  }

}
