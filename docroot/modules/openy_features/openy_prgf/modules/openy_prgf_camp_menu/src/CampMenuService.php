<?php

namespace Drupal\openy_prgf_camp_menu;

use Drupal\node\NodeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Entity\Query\QueryFactory;

/**
 * Class CampMenuService.
 *
 * @package Drupal\openy_prgf_camp_menu
 */
class CampMenuService implements CampMenuServiceInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * Site config object.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $config;

  /**
   * Entity query object.
   *
   * @var \Drupal\Core\Entity\Query\QueryFactory
   */
  protected $queryFactory;

  /**
   * Constructs a new CampMenuService.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity manager.
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory service.
   * @param \Drupal\Core\Entity\Query\QueryFactory $query_factory
   *   Entity query object.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    Connection $connection,
    ConfigFactoryInterface $config_factory,
    QueryFactory $query_factory) {
    $this->entityTypeManager = $entity_type_manager;
    $this->connection = $connection;
    $this->config = $config_factory->get('system.site');
    $this->queryFactory = $query_factory;
  }

  /**
   * Returns boolean if provided node is a camp or is related to one.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node.
   *
   * @return bool
   *   True when a camp or related to a camp, else False.
   */
  public function nodeHasOrIsCamp(NodeInterface $node) {
    if ($this->getNodeCampNode($node) == NULL) {
      return FALSE;
    }
    return TRUE;
  }

  /**
   * Retrieves referenced Camp node for the node.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node.
   *
   * @return \Drupal\node\NodeInterface|null
   *   Camp node or NULL.
   */
  public function getNodeCampNode(NodeInterface $node) {
    $camp = NULL;
    switch ($node->bundle()) {
      case 'camp':
        $camp = $node;
        break;

      case 'landing':
        $camp = $this->getLandingPageCampNode($node);
        break;
    }

    return $camp;
  }

  /**
   * Retrieves Camp menu for any node.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node.
   *
   * @return array
   *   Array of menu links.
   */
  public function getNodeCampMenu(NodeInterface $node) {
    if (!$camp = $this->getNodeCampNode($node)) {
      return [];
    }

    return $this->getCampNodeCampMenu($camp);
  }

  /**
   * Retrieves Camp menu for the Camp CT node.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The Camp node.
   *
   * @return array
   *   Array of menu links.
   */
  private function getCampNodeCampMenu(NodeInterface $node) {
    if ($node->bundle() != 'camp') {
      return [];
    }

    $links = [];
    foreach ($node->field_camp_menu_links->getValue() as $link) {
      $links[] = $link;
    }

    return $links;
  }

  /**
   * Retrieves the Camp CT associated to the landing page provided.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node.
   *
   * @return \Drupal\node\Entity\Node|mixed|null
   *   The camp node or Null.
   */
  private function getLandingPageCampNode(NodeInterface $node) {
    $camp = NULL;
    // Exit if the node is not a landing page.
    if ($node->bundle() != 'landing') {
      return $camp;
    }
    // If the node has the Location field.
    if ($node->hasField('field_location')) {
      if ($value = $node->field_location->referencedEntities()) {
        // referencedEntities() returns an array of entities, since the location
        // field can only have a single value we reset to get that node object.
        $camp = reset($value);
      }
    }
    // Else if a camp links to this landing page use the linking camp.
    else {
      // Need to get <front> path if set & is a node.
      $front = $this->config->get('page.front');
      // To track if this node is the front page.
      $is_front = FALSE;
      // Setup to lookup all aliases for this node.
      $langcode = $node->language()->getId();
      $system_path = '/node/' . $node->id();
      // If the front is set to this node directly.
      $is_front = ($front == $system_path) ? TRUE : $is_front;
      // Query 1 Camp nodes that link to this landing page.
      $query = $this->queryFactory->get('node');
      $group = $query->orConditionGroup()
        ->condition('field_camp_menu_links', 'entity:node/' . $node->id())
        ->condition('field_camp_menu_links', 'internal:' . $system_path);
      // Since the link field allows internal links we must check if this node's
      // aliases are linked also.
      if ($aliases = $this->lookupPathAliases($system_path, $langcode)) {
        foreach ($aliases as $alias) {
          $group->condition('field_camp_menu_links', 'internal:' . $alias->alias);
          // Checking to see if the alias is the front page config value.
          $is_front = ($front == $alias->alias) ? TRUE : $is_front;
        }
      }
      // If this node is the front page we add the '/' path. This is how <front>
      // is represented in the link field storage.
      if ($is_front) {
        $group->condition('field_camp_menu_links', 'internal:/');
      }
      $query->condition('status', 1)
        ->condition($group)
        ->range(0, 1);
      $entity_ids = $query->execute();
      // If results returned.
      if (!empty($entity_ids)) {
        $node_storage = $this->entityTypeManager->getStorage('node');
        $camp = $node_storage->load(reset($entity_ids));
      }
    }
    return $camp;
  }

  /**
   * Returns all aliases of Drupal system URL.
   *
   * Neither AliasManagerInterface or
   * AliasStorageInterface have a method to get all aliases for a path.
   *
   * @param string $path
   *   The path to investigate for corresponding path aliases.
   * @param string $langcode
   *   Language code to search the path with. If there's no path defined for
   *   that language it will search paths without language.
   *
   * @return string|false
   *   A path alias, or FALSE if no path was found.
   *
   * @see \Drupal\Core\Path\AliasManagerInterface
   * @see \Drupal\Core\Path\AliasStorageInterface
   * @see \Drupal\Core\Path\AliasStorage::lookupPathAlias
   */
  public function lookupPathAliases($path, $langcode) {
    $source = $this->connection->escapeLike($path);
    $langcode_list = [$langcode, LanguageInterface::LANGCODE_NOT_SPECIFIED];
    $alias_table = 'url_alias';
    // See the queries above. Use LIKE for case-insensitive matching.
    $select = $this->connection->select($alias_table)
      ->fields($alias_table, ['alias'])
      ->condition('source', $source, 'LIKE');
    if ($langcode == LanguageInterface::LANGCODE_NOT_SPECIFIED) {
      array_pop($langcode_list);
    }
    elseif ($langcode > LanguageInterface::LANGCODE_NOT_SPECIFIED) {
      $select->orderBy('langcode', 'DESC');
    }
    else {
      $select->orderBy('langcode', 'ASC');
    }
    $select->orderBy('pid', 'DESC');
    $select->condition('langcode', $langcode_list, 'IN');
    try {
      return $select->execute()->fetchall();
    }
    catch (\Exception $e) {
      $this->catchException($e);
      return FALSE;
    }
  }

}
