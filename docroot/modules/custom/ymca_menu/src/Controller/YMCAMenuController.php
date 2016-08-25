<?php

namespace Drupal\ymca_menu\Controller;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheableJsonResponse;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Url;
use Drupal\Core\Controller\ControllerBase;

/**
 * Responses for menu json object calls.
 */
class YMCAMenuController extends ControllerBase {

  /**
   * Root page id.
   */
  const ROOT_ID = 1;

  const MOBILE_MENU_LIST_CONFIG_NAME = 'ymca_menu.mobile_menu_list';

  /**
   * Outputs JSON-response.
   */
  public function json() {
    $data = $this->buildTree(self::MOBILE_MENU_LIST_CONFIG_NAME);

    $cacheable = [
      '#cache' => [
        'tags' => $this->getMenuTags(self::MOBILE_MENU_LIST_CONFIG_NAME),
      ],
    ];

    $response = new CacheableJsonResponse($data, 200);
    $response->addCacheableDependency(CacheableMetadata::createFromRenderArray($cacheable));
    return $response;
  }

  /**
   * Builds sitemap tree.
   *
   * @param string $config
   *   Menu list config name.
   *
   * @return array
   *   An array of the menu tree.
   */
  public function buildTree($config = 'ymca_menu.menu_list') {
    // config:ymca_menu.menu_list tag.
    $cache_id = 'ymca_menu_buildTree_' . $config;
    if ($cache = $this->cache()->get($cache_id)) {
      return $cache->data;
    }

    // Lookup stores all menu-link items.
    $tree = $this->initTree();
    $menus = static::menuList($config);
    foreach ($menus as $menu_id) {
      $query = db_select('menu_tree', 'mt');
      $query->leftJoin('menu_link_content', 'mlc', 'mt.id = CONCAT(mlc.bundle, :separator, mlc.uuid)', [':separator' => ':']);
      $query->leftJoin('menu_link_content_data', 'mlcd', 'mlcd.id = mlc.id');
      $query->condition('mt.menu_name', $menu_id);
      $query->fields('mt', array(
        'mlid',
        'id',
        'parent',
        'url',
        'p1',
        'p2',
        'p3',
        'p4',
        'p5',
        'p6',
        'p7',
        'p8',
        'title',
        'depth',
        'weight',
        'enabled',
      ));
      $query->fields('mlcd', array('link__uri'));
      $query->addField('mlcd', 'id', 'mlcid');
      $query
        ->orderBy('mt.depth')
          ->orderBy('mt.weight');

      $results = $query->execute();
      $rows = [];
      foreach ($results as $key => $row) {
        // Exceptions.
        // Skip 'Home' link.
        if ($menu_id == 'main-menu' && $key === 0) {
          continue;
        }
        if ($menu_id == 'main-menu' && unserialize($row->title) == 'Locations') {
          $locations_parent = $row->mlid;
        }
        // Skip location root.
        if ($menu_id == 'locations' && $key === 0) {
          $locations_root = $row->mlid;
          continue;
        }
        $row->menu_id = $menu_id;

        $rows[$row->id] = $row;
      }

      foreach ($rows as $row) {
        // Point to parent tree-node and collect parents.
        $ctree = &$tree->tree[self::ROOT_ID];
        $ancestors = [(string) self::ROOT_ID];
        for ($i = 1; $i < 9; $i++) {
          if (!empty($row->{'p' . $i}) && $row->{'p' . $i} != $row->mlid) {
            $anc_mlid = $row->{'p' . $i};
            if ($menu_id == 'locations' && $anc_mlid == $locations_root && isset($locations_parent)) {
              $anc_mlid = $locations_parent;
            }
            $ancestors[] = $anc_mlid;
            $ctree = &$ctree[$anc_mlid];
          }
        }
        $tree->lookup[$row->mlid] = array(
          'a' => $ancestors,
          // Isn't used.
          'b' => 'smth',
          'l' => $row->depth,
          'n' => unserialize($row->title),
          't' => unserialize($row->title),
          'u' => '',
          'm' => $row->menu_id,
        );
        if ($row->link__uri) {
          try {
            $tree->lookup[$row->mlid]['u'] = Url::fromUri($row->link__uri)->toString(TRUE)->getGeneratedUrl();
          }
          catch (\InvalidArgumentException $e) {
            try {
              $tree->lookup[$row->mlid]['u'] = Url::fromUserInput($row->link__uri)->toString(TRUE)->getGeneratedUrl();
            }
            catch (\InvalidArgumentException $e) {
              $menu_item_page_uri = Url::fromRoute(
                'entity.menu_link_content.edit_form',
                array(
                  'menu_link_content' => $row->mlcid,
                ));
              \Drupal::logger('ymca_menu')
                ->error('[DEV] Menu link path %path cannot be converted to URL. Check at <a href="@url">page</a>', [
                  '%path' => $row->link__uri,
                  '@url' => $menu_item_page_uri->toString(TRUE)->getGeneratedUrl(),
                ]);
            }
          }
        }
        // Exclude from nav if menu item is disabled.
        if (!$row->enabled) {
          $tree->lookup[$row->mlid]['x'] = 1;
        }
        // Menu items order.
        $ctree['o'][] = $row->mlid;
        // Empty array for children.
        $ctree[$row->mlid] = [];
      }
    }

    $this->cache()->set($cache_id, $tree, Cache::PERMANENT, $this->getMenuTags($config));

    return $tree;
  }

  /**
   * Helper function; retrieve menu cache tags.
   *
   * @param string $config
   *   Name of menu list config.
   *
   * @return array
   *   Array of cache tags.
   */
  private function getMenuTags($config) {
    $menu_tags = ['config:' . $config, 'node_list', YMCA_MENU_CACHE_CID];
    $menus = static::menuList($config);
    foreach ($menus as $menu_id) {
      $menu_tags[] = 'config:system.menu.' . $menu_id;
    }
    return $menu_tags;
  }

  /**
   * Init JSON sitemap tree object.
   *
   * @return \stdClass
   *   Sitemap tree object, containing only the root.
   */
  private function initTree() {
    $tree = new \stdClass();
    $tree->map = $this->defaultMap();
    $tree->lookup = [];
    $tree->tree = [];

    // Add root.
    $tree->lookup[self::ROOT_ID] = array(
      'a' => [],
      'b' => 'home',
      'n' => 'Home',
      't' => t('Home'),
      'u' => "/",
    );
    $tree->tree[self::ROOT_ID] = [];
    $tree->tree['o'] = [self::ROOT_ID];

    return $tree;
  }

  /**
   * Returns default sitemap data mapping.
   *
   * @return array
   *   Data mapping.
   */
  private function defaultMap() {
    return [
      'abe_page' => "d",
      'ancestry' => "a",
      'exclude_from_nav' => "x",
      'magic_page' => "m",
      'nav_level' => "l",
      'order' => "o",
      'page_abbr' => "b",
      'page_name' => "n",
      'page_title' => "t",
      'url' => "u",
    ];
  }

  /**
   * Return an ordered list of menus' machine names to be combined.
   *
   * @param string $config
   *   Optional config id that can override default settings. Useful in a/b.
   *
   * @return array
   *   List of menu machine names.
   */
  public static function menuList($config = 'ymca_menu.menu_list') {
    $menu_list = \Drupal::config($config)->get('menu_list');
    return $menu_list;
  }

  /**
   * Builds Main menu configuration page.
   */
  public function configMainMenu($variant) {
    $form = $this->formBuilder()->getForm('Drupal\ymca_menu\Form\YmcaMainMenuConfigForm', $variant);
    return [
      'form' => $form,
      '#cache' => [
        'max-age' => 0,
      ],
    ];
  }

}
