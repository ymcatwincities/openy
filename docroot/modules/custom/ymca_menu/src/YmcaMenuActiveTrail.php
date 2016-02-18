<?php
/**
 * @file
 * Contains Drupal\ymca_menu\YmcaMenuActiveTrail.
 */

namespace Drupal\ymca_menu;

use Drupal\Core\Menu\MenuActiveTrail;
use Drupal\node\Entity\NodeType;
use Drupal\node\NodeInterface;

define('TERM_NEWS_TID', 6);

/**
 * Extend the MenuActiveTrail class.
 */
class YmcaMenuActiveTrail extends MenuActiveTrail {

  /**
   * {@inheritdoc}
   */
  public function getActiveLink($menu_name = NULL) {
    // Call the parent method to implement the default behavior.
    $found = parent::getActiveLink($menu_name);

    $route_name = \Drupal::routeMatch()->getRouteName();
    if ($route_name == 'ymca_groupex.all_schedules_search_results') {
      $route_name_matched = 'ymca_groupex.all_schedules_search';
    }

    // If a node is displayed, load the default parent menu item
    // from the node type's menu settings and return it instead
    // of the default one.
    $node = $this->routeMatch->getParameter('node');

    if ($node instanceof NodeInterface) {
      $bundle = $node->bundle();
      switch ($bundle) {
        case 'location':
          $route_name_matched = 'ymca_frontend.locations';
          break;

        case 'blog':
          $route_name_matched = 'view.ymca_twin_cities_blog.blog_page';
          if ($field_tags_value = $node->field_tags->getValue()) {
            foreach ($field_tags_value as $id) {
              if ($id['target_id'] == TERM_NEWS_TID) {
                $route_name_matched = 'view.ymca_news.page_news';
              }
            }
          }
          break;
      }
    }

    if (isset($route_name_matched)) {
      $links = \Drupal::service('plugin.manager.menu.link')
        ->loadLinksByRoute($route_name_matched);
      if ($links) {
        foreach ($links as $link) {
          if ($link->getMenuName() == 'top-menu') {
            $found = $link;
          }
        }
      }
    }

    return $found;
  }
}
