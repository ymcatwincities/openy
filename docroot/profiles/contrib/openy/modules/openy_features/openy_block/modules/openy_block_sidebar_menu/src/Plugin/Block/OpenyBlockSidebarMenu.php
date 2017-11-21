<?php

namespace Drupal\openy_block_sidebar_menu\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Menu\MenuLinkTreeInterface;
use Drupal\Core\Menu\MenuTreeParameters;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\openy_menu_tree\SidebarMenuInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a "Sidebar Menu" block.
 *
 * @Block(
 *   id = "openy_block_sidebar_menu",
 *   admin_label = @Translation("Openy Sidebar Menu Block"),
 *   category = @Translation("Blocks")
 * )
 */
class OpenyBlockSidebarMenu extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * Menu Tree.
   *
   * @var \Drupal\Core\Menu\MenuLinkTreeInterface
   */
  protected $menuTree;

  /**
   * Renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * Sidebar Menu.
   *
   * @var \Drupal\openy_menu_tree\SidebarMenuInterface
   */
  protected $sidebarMenu;

  /**
   * OpenyBlockSidebarMenu constructor.
   *
   * @param array $configuration
   *   Configuration.
   * @param string $plugin_id
   *   Plugin ID.
   * @param mixed $plugin_definition
   *   Plugin definition.
   * @param \Drupal\Core\Menu\MenuLinkTreeInterface $menu_tree
   *   Menu Tree.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   Renderer.
   * @param \Drupal\openy_menu_tree\SidebarMenuInterface $sidebar_menu
   *   Sidebar menu.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, MenuLinkTreeInterface $menu_tree, RendererInterface $renderer, SidebarMenuInterface $sidebar_menu) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->menuTree = $menu_tree;
    $this->renderer = $renderer;
    $this->sidebarMenu = $sidebar_menu;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('menu.link_tree'),
      $container->get('renderer'),
      $container->get('openy_menu_tree.sidebar_menu')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $node = \Drupal::routeMatch()->getParameter('node');
    $menu_name = $this->sidebarMenu->findMenu($node);
    if (!$menu_name) {
      return FALSE;
    }

    // @todo Here we could set levels, depth, etc.
    /** @var MenuTreeParameters $parameters */
    $parameters = $this->menuTree->getCurrentRouteMenuTreeParameters($menu_name);
    $tree = $this->menuTree->load($menu_name, $parameters);
    $manipulators = [
      ['callable' => 'menu.default_tree_manipulators:checkAccess'],
      ['callable' => 'menu.default_tree_manipulators:generateIndexAndSort'],
    ];
    $tree = $this->menuTree->transform($tree, $manipulators);
    $menu = $this->menuTree->build($tree);
    return [
      '#markup' => $this->renderer->render($menu),
    ];
  }

}
