<?php

namespace Drupal\ymca_menu;

use Drupal\Core\Database\Database;
use Drupal\ymca_menu\Controller\YMCAMenuController;

/**
 * Provides YMCA Menu builder class.
 */
class YMCAMenuBuilder {

  protected $tree;
  protected $lookup;
  protected $map;
  protected $cache;
  protected $rootId = '';
  protected $pageId = '';

  /**
   * These will be reverse looked up from the map.
   */
  protected $ancestryKey = '';
  protected $excludeFromNavKey = '';
  protected $orderKey = '';

  /**
   * These get added to the new array as its built.
   */
  protected $childrenKey = 'children';
  protected $pageIdKey = 'site_page_id';

  /**
   * Constructs a YMCAMenuBuilder object.
   *
   * @param string $ab
   *   If equals to 'b', works with alternative menu tree.
   */
  public function __construct($ab = '') {
    $this->pageId = $this->getActiveMlid();

    $ymca_menu = new YMCAMenuController();
    $this->menu_tree = $ymca_menu->buildTree();

    $this->tree   = $this->menu_tree->tree;
    $this->lookup = $this->menu_tree->lookup;
    $this->map    = $this->menu_tree->map;

    // Set the key names being used for these attributes.
    $this->ancestryKey       = $this->map['ancestry'];
    $this->excludeFromNavKey = $this->map['exclude_from_nav'];
    $this->orderKey          = $this->map['order'];

    foreach ($this->tree as $id => $value) {
      if ($id != $this->orderKey) {
        $this->rootId = $id;
        break;
      }
    }

    // Load meganav menu link id parents.
    $config_name = $ab == 'b' ? 'ymca_menu.main_menu_b' : 'ymca_menu.main_menu';
    $this->megaNav = \Drupal::config($config_name)->get('items');
  }

  /**
   * Main method which retrives active menu tree.
   *
   * Get the navigation to be used for a given page. Handles pruning of pages
   * that are excluded from nav except those that are parents, children or
   * siblings of the page being requested.
   *
   * Multiple requests may be made for the same page and will be cached.
   *
   * @returns object
   *   Active menu tree.
   */
  public function get($ab = 'a') {
    $level = 0;

    // Check for page existance.
    if (!$this->lookup[$this->pageId]) {
      return FALSE;
    }

    // So that we can make multiple requests for the same page node
    // without recalculating but calls to other nodes can also be made
    // without corrupting page tree data.
    if (!isset($this->cache[$ab][$this->pageId])) {
      $this->cache[$ab][$this->pageId] = $this->getBranch($this->rootId, $this->tree[$this->rootId], $level);
    }

    return $this->cache[$ab][$this->pageId];
  }

  /**
   * Function that recursively iterates through page tree.
   *
   * @param int $page_id
   *   ID of current page.
   * @param object $page_tree
   *   Page tree of current branch.
   * @param int $level
   *   Current level of tree.
   *
   * @return object
   *   Contains children of menu tree.
   */
  public function getBranch($page_id, $page_tree, $level) {

    $next_level     = $level + 1;
    $next_page_tree = isset($page_tree[$page_id]) ? $page_tree[$page_id] : [];
    $branch         = [];

    $branch                   = $this->decoratePage($page_id);
    $branch[$this->pageIdKey] = $page_id;

    if (isset($page_tree[$this->orderKey])) {
      foreach ($page_tree[$this->orderKey] as $key => $value) {
        $id = $page_tree[$this->orderKey][$key];

        // If the page we're actively requesting is exluded from nav, we
        // want to keep parents, siblings and children in the nav structure.
        if ($this->hideFromNav($id)) {
          continue;
        }

        if (!isset($branch[$this->childrenKey])) {
          $branch[$this->childrenKey] = [];
        }
        $branch[$this->childrenKey][] = $this->getBranch($id, $page_tree[$id], $next_level);
      }
    }

    return $branch;
  }

  /**
   * Hide from navigation.
   *
   * Figure out if the page being evaluated should be hidden in the structure
   * because it's excluded from nav and we're not on it or a
   * sibling/child, etc.
   *
   * @param int $page_id
   *   ID of page being evaluated.
   *
   * @return bool
   *   Indicates if page is hidden from nav.
   */
  public function hideFromNav($page_id) {

    if (!isset($this->lookup[$page_id])) {
      return TRUE;
    }

    $requested_page = $this->lookup[$this->pageId];
    $page           = $this->lookup[$page_id];

    if (isset($page[$this->excludeFromNavKey])) {

      // If the requested page IS the page we're testing against.
      if ($page_id == $this->pageId) {
        return FALSE;
      }

      // If the page we're testing against is a child (as long as it's
      // not the root of the tree because everything is a child of that!)
      // OR the page being requested is the child of the page were
      // iterating over.
      if ($this->pageId != $this->rootId
        && (isset($page[$this->ancestryKey][$this->pageId]))
        || isset($requested_page[$this->ancestryKey][$page_id])) {
        return FALSE;
      }

      return TRUE;
    }

    return FALSE;
  }

  /**
   * Page decorator.
   *
   * Page hash decorator to add the nice active/parent/current and any other
   * future niceities. This function also calls the reverse attribute mapping
   * function so that the abbreviated keys are replaced with the standard
   * attribute names, eg n => page_name.
   *
   * @param int $page_id
   *   ID of page being decorated.
   *
   * @return object
   *   Page with added attributes.
   */
  public function decoratePage($page_id) {
    $page           = $this->mapAttributes($this->lookup[$page_id]);
    $requested_page = $this->lookup[$this->pageId];

    if (in_array($page_id, $requested_page[$this->ancestryKey])) {
      $page['parent'] = TRUE;
      $page['active'] = TRUE;
    }

    if ($this->pageId == $page_id) {
      $page['current'] = TRUE;
      $page['active']  = TRUE;
    }

    $page['show_in_meganav'] = !empty($this->megaNav[$page_id]['show']);
    $page['show_overview_link'] = !empty($this->megaNav[$page_id]['overview']);

    $dasherized_page_name = strtolower($page['page_name']);
    $dasherized_page_name = str_replace(['&', '.', ','], '', $dasherized_page_name);
    $dasherized_page_name = str_replace(' ', '-', $dasherized_page_name);
    $page['dasherized'] = $dasherized_page_name;

    return $page;
  }

  /**
   * Remap data in structure from single letter keys to full attribute names.
   *
   * @param object $page
   *   Hash of page to remap.
   *
   * @return object
   *   New page object.
   */
  public function mapAttributes($page) {
    $new_page = [];

    foreach ($this->map as $key => $value) {
      if (isset($page[$value])) {
        $new_page[$key] = $page[$value];
      }
    }

    return $new_page;
  }

  /**
   * Returns active menu link id current page.
   *
   * @return int
   *   Menu link ID.
   */
  public function getActiveMlid() {
    $route_name = \Drupal::routeMatch()->getRouteName();
    $mlid = FALSE;
    if (in_array($route_name, ['entity.node.canonical', 'entity.node.preview'])) {
      $node = \Drupal::routeMatch()->getParameter('node');
      if ($route_name == 'entity.node.preview') {
        $node = \Drupal::routeMatch()->getParameter('node_preview');
      }
      $context = \Drupal::service('pagecontext.service')->getContext();
      if ($node->bundle() == 'blog' && $context && ($context->bundle() == 'camp' || $context->bundle() == 'location')) {
        $route_parameters = ['node' => $context->id()];
        $menu_name = $context->bundle() . 's';
        // Search News & Events page of context camp.
        $links = \Drupal::service('plugin.manager.menu.link')
          ->loadLinksByRoute('ymca_blog_listing.news_events_page_controller', $route_parameters, $menu_name);
        if ($links) {
          // Select the first matching link.
          $found = reset($links);
          if ($found->isEnabled()) {
            $connection = Database::getConnection();
            $query = $connection
              ->select('menu_tree', 'mt')
                ->fields('mt', array('mlid'))
                ->condition('id', $found->getPluginId());
            $mlid = (int) $query->execute()->fetchField();
          }
        }
      }
    }
    if (!$mlid) {
      $menus = YMCAMenuController::menuList();
      $context = \Drupal::service('pagecontext.service')->getContext();
      if ($context && in_array($context->bundle(), ['location', 'camp'])) {
        // Alter menu list to prioritize corresponding menus for locations/camps.
        switch ($context->bundle()) {
          case 'location':
            array_unshift($menus, 'locations');
            break;

          case 'camp':
            array_unshift($menus, 'camps');
            break;
        }
      }

      foreach ($menus as $menu) {
        /* @var \Drupal\menu_link_content\Plugin\Menu\MenuLinkContent $link */
        if ($link = \Drupal::service('menu.active_trail')->getActiveLink($menu)) {
          $connection = Database::getConnection();
          $query = $connection
            ->select('menu_tree', 'mt')
              ->fields('mt', array('mlid'))
              ->condition('id', $link->getPluginId());
          $mlid = (int) $query->execute()->fetchField();
          break;
        }
      }
    }
    return !empty($mlid) ? $mlid : YMCAMenuController::ROOT_ID;
  }

  /**
   * {@inheritdoc}
   */
  public function getActiveMenuTree($ab = '') {
    // Load meganav menu link id parents.
    $old_value = $this->megaNav;
    // Set new megaNav value.
    $config_name = $ab == 'b' ? 'ymca_menu.main_menu_b' : 'ymca_menu.main_menu';
    $this->megaNav = \Drupal::config($config_name)->get('items');

    $active_menu_tree = $this->get($ab == 'b' ? 'b' : 'a');
    // Restore old megaNav value.
    $this->megaNav = $old_value;

    return $active_menu_tree;
  }

}
