<?php

/**
 * @file
 * Contains \Drupal\ymca_menu\Controller\YMCAMenuController.
 */

namespace Drupal\ymca_menu\Controller;

use Drupal\Core\Database\Connection;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Responses for menu json object calls.
 */
class YMCAMenuController extends ControllerBase {

  /**
   * The database service.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('database'));
  }

  /**
   * Constructs a YMCAMenuController object.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   A database connection.
   */
  public function __construct(Connection $database) {
    $this->database = $database;
  }

   /**
   * Outputs JSON-response.
   */
  public function json() {
    $options = $this->buildTree();
    return new JsonResponse($options);
  }

  /**
   * Builds sitemap tree.
   */
  private function buildTree() {
    // Lookup stores all menu-link items.
    $tree = $this->initTree();

    // The order of menus to be combined.
    $menus = [
      'main-menu',
      'locations',
      'health-and-fitness',
      'swimming',
      'child-care-preschool',
      'kids-teen-activities',
      'camps',
      'community-programs',
      'jobs-suppliers-news',
    ];
    foreach ($menus as $menu_id) {
      $query = $this->database
        ->select('menu_tree', 'mt')
        ->condition('menu_name', $menu_id);
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
      $query
        ->orderBy('depth')
        ->orderBy('weight');

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

        $rows[$row->id] = $row;
      }

      foreach ($rows as $row) {
        // Point to parent tree-node and collect parents.
        $ctree = &$tree->tree[0];
        $ancestors = [0];
        for ($i = 1; $i < 9; $i++) {
          if (!empty($row->{'p' . $i}) && $row->{'p' . $i} != $row->mlid) {
            $anc_mlid = $row->{'p' . $i};
            if ($menu_id == 'locations' && $anc_mlid == $locations_root) {
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
          'u' => $row->url,
        );
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

    return $tree;
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
    $tree->lookup[0] = array(
      'a' => [],
      'b' => 'home',
      'n' => 'Home',
      't' => t('Home'),
      'u' => "/",
    );
    $tree->tree[0] = [];
    $tree->tree['o'] = [0];

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
}
