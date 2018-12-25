<?php

namespace Drupal\ymca_aliases;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Path\AliasManager;
use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;
use Drupal\menu_link_content\Entity\MenuLinkContent;
use Drupal\Core\Menu\MenuTreeStorageInterface;
use Drupal\Core\Url;

/**
 * Builds an alias for a node.
 *
 * @package Drupal\ymca_aliases.
 */
class NodeAlias {

  /**
   * News taxonomy term id.
   *
   * @var int
   */
  const NEWS_TERM_ID = 6;

  /**
   * Url cleaner.
   *
   * @var UrlCleaner
   */
  protected $urlCleaner;

  /**
   * Alias manager.
   *
   * @var AliasManager
   */
  protected $aliasManager;

  /**
   * Menu tree storage.
   *
   * @var MenuTreeStorageInterface
   */
  protected $treeStorage;

  /**
   * NodeAlias constructor.
   */
  public function __construct(UrlCleaner $url_cleaner, AliasManager $alias_manager, MenuTreeStorageInterface $tree_storage) {
    $this->urlCleaner = $url_cleaner;
    $this->aliasManager = $alias_manager;
    $this->treeStorage = $tree_storage;
  }

  /**
   * Get alias.
   *
   * @param \Drupal\node\NodeInterface $node
   *   Node object.
   *
   * @return null|string
   *   Generated alias.
   */
  public function getAlias(NodeInterface $node) {
    $alias = NULL;

    switch ($node->bundle()) {
      case 'blog':
        $tz = new DrupalDateTime(\Drupal::config('system.date')->get('timezone')['default']);
        $created = $node->get('created')->getValue()[0]['value'];
        $date = DrupalDateTime::createFromTimestamp($created, $tz);

        $url_parts = [
          'year' => $date->format('Y'),
          'month' => $date->format('m'),
          'day' => $date->format('d'),
          'id' => $node->id(),
          'suffix' => $this->urlCleaner->clean($node->getTitle()),
        ];

        // The post belongs to a camp or a location.
        $section = $node->get('field_site_section');
        if (!$section->isEmpty()) {
          $id = $section->getValue()[0]['target_id'];
          $target_node = Node::load($id);
          if ($target_node->bundle() == 'camp') {
            $target_alias = $this->aliasManager->getAliasByPath('/node/' . $id);
            preg_match('/\/camps\/(\w+)/', $target_alias, $test);
            if (!empty($test[1])) {
              $url_parts = array_merge(['prefix' => $test[1] . '_news__events'], $url_parts);
              $alias = '/' . implode('/', $url_parts);
              break;
            }
          }
        }

        // Check whether the post has 'news' tag.
        $tags = $node->get('field_tags');
        if (!$tags->isEmpty()) {
          foreach ($tags->getValue() as $item) {
            if ($item['target_id'] == self::NEWS_TERM_ID) {
              $url_parts = array_merge(['prefix' => 'news'], $url_parts);
              $alias = '/' . implode('/', $url_parts);
              break;
            }
          }
        }

        // Set 'blog' or 'day_camp_news' prefix.
        $prefix = ['prefix' => 'blog'];
        if ($node->hasField('field_related_camps_locations')) {
          $day_camp = $node->get('field_related_camps_locations');
          if (!$day_camp->isEmpty()) {
            $prefix['prefix'] = 'day_camp_news';
          }
        }

        $url_parts = array_merge($prefix, $url_parts);
        $alias = '/' . implode('/', $url_parts);

        break;

      // Pattern is [parent-menu-item-path]/[node-title].
      case 'article':
        if (!$node->id()) {
          // Node not yet saved. Figuring out the future path.
          /** @var \Drupal\node\Entity\NodeType $node_type */
          $node_type = $node->type->entity;
          $tps = $node_type->getThirdPartySetting('menu_ui', 'parent');
          $parent = explode(':', $tps);
          if (isset($parent[2])) {
            $pid = \Drupal::entityManager()->loadEntityByUuid('menu_link_content', $parent[2]);
            $uri = $pid->get('link')->first()->uri;
            $url = Url::fromUri($uri);
            if ($url->isRouted()) {
              $alias = \Drupal::service('path.alias_manager')->getAliasByPath('/' . $url->getInternalPath(), 'en');
            }
            elseif ($url->isExternal()) {
              // Can't build alias, if the parent menu item has ån external link.
              $alias = '';
            }
            else {
              if ($uri_parts = parse_url($uri)) {
                if ($uri_parts['scheme'] == 'internal') {
                  $alias = $uri_parts['path'];
                }
              }
            }
            // Add cleaned title to the end of alias.
            $alias .= '/' . $this->urlCleaner->clean($node->getTitle());
            break;
          }
        }

        $defaults = menu_ui_get_menu_link_defaults($node);
        if (empty($defaults['id'])) {
          // There is no parent menu link, the node isn't in menu tree.
          return '/' . $this->urlCleaner->clean($node->getTitle());
        }
        // Get current menu link content entity associated with the node.
        $menu_link = MenuLinkContent::load($defaults['entity_id']);
        $parent_id = $menu_link->getParentId();
        if (!$parent_id) {
          // There is no parent menu link, the node is root.
          $alias = '';
        }
        else {
          // Parent menu link plugin definition.
          $parent_menu_link_array = $this->treeStorage->load($parent_id);
          $parent_mlid = $parent_menu_link_array['metadata']['entity_id'];

          // Parent menu link content entity.
          $parent_menu_link = MenuLinkContent::load($parent_mlid);
          $uri = $parent_menu_link->get('link')->first()->uri;
          $url = Url::fromUri($uri);
          if ($url->isRouted()) {
            $alias = \Drupal::service('path.alias_manager')->getAliasByPath('/' . $url->getInternalPath(), 'en');
          }
          elseif ($url->isExternal()) {
            // Can't build alias, if the parent menu item has ån external link.
            $alias = '';
          }
          else {
            if ($uri_parts = parse_url($uri)) {
              if ($uri_parts['scheme'] == 'internal') {
                $alias = $uri_parts['path'];
              }
            }
          }
        }

        // Add cleaned title to the end of alias.
        $alias .= '/' . $this->urlCleaner->clean($node->getTitle());
        break;
    }

    return $alias;
  }

}
