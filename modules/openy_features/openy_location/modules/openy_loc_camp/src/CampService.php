<?php

namespace Drupal\openy_loc_camp;

use Drupal\node\NodeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * Class CampService contains methods for camp related processes.
 *
 * @package Drupal\openy_loc_camp\CampService
 */
class CampService {

  /**
   * Entity type manager.
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
   * Constructs a EntityCreateAccessCheck object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Entity type manager.
   * @param \Drupal\Core\Database\Connection $connection
   *   A database connection for reading and writing path aliases.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory service.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, Connection $connection, ConfigFactoryInterface $config_factory) {
    $this->entityTypeManager = $entityTypeManager;
    $this->connection = $connection;
    $this->config = $config_factory->get('system.site');
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

      case 'landing_page':
        $camp = $this->getLandingPageCampNode($node);
        break;
    }

    return $camp;
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
  public function getLandingPageCampNode(NodeInterface $node) {
    $camp = NULL;
    // Exit if the node is not a landing page.
    if ($node->bundle() != 'landing_page') {
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
      $query = \Drupal::entityQuery('node');
      $group = $query->orConditionGroup()
        ->condition('field_camp_menu_links', 'entity:node/' . $node->id())
        ->condition('field_camp_menu_links', 'internal:' . $system_path);

      // Since the link field allows internal links we must check if this node's
      // aliases are linked also.
      if ($aliases = $this->lookupPathAliases($system_path, $langcode)) {
        foreach ($aliases as $alias) {
          if(empty($alias->alias->value)) {
            continue;
          }
          $group->condition('field_camp_menu_links', 'internal:' . $alias->alias->value);
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
   * @see \Drupal\path_alias\AliasManagerInterface
   * @see \Drupal\path_alias\PathAliasStorage
   * @see \Drupal\path_alias\AliasManager::getAliasByPath()
   */
  public function lookupPathAliases($path, $langcode) {
    $source = $this->connection->escapeLike($path);
    $langcode_list = [$langcode, LanguageInterface::LANGCODE_NOT_SPECIFIED];

    try {
      return $this->entityTypeManager->getStorage('path_alias')
        ->loadByProperties(['path' => $path, 'langcode' => $langcode_list]);
    } catch (\Exception $e) {
      return FALSE;
    }
  }

}
