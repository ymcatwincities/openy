<?php

/**
 * @file
 * Contains \Drupal\default_content\DefaultContentManager.
 */

namespace Drupal\default_content;

use Drupal\Component\Graph\Graph;
use Drupal\Component\Utility\SafeMarkup;
use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityManager;
use Drupal\Core\Session\AccountInterface;
use Drupal\rest\LinkManager\LinkManagerInterface;
use Drupal\rest\Plugin\Type\ResourcePluginManager;
use Symfony\Component\Serializer\Serializer;

/**
 * A service for handling import of default content.
 * @todo throw useful exceptions
 */
class DefaultContentManager implements DefaultContentManagerInterface {

  const LINK_DOMAIN = 'http://drupal.org';

  /**
   * The serializer service.
   *
   * @var \Symfony\Component\Serializer\Serializer
   */
  protected $serializer;

  /**
   * The rest resource plugin manager.
   *
   * @var \Drupal\rest\Plugin\Type\ResourcePluginManager
   */
  protected $resourcePluginManager;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * The file system scanner.
   *
   * @var \Drupal\default_content\DefaultContentScanner
   */
  protected $scanner;

  /**
   * A list of vertex objects keyed by their link.
   *
   * @var array
   */
  protected $vertexes = array();

  /**
   * The graph entries.
   *
   * @var array
   */
  protected $graph = [];

  /**
   * The link manager service.
   *
   * @var \Drupal\rest\LinkManager\LinkManagerInterface
   */
  protected $linkManager;

  /**
   * Constructs the default content manager.
   *
   * @param \Symfony\Component\Serializer\Serializer $serializer
   *   The serializer service.
   * @param \Drupal\rest\Plugin\Type\ResourcePluginManager $resource_plugin_manager
   *   The rest resource plugin manager.
   * @param \Drupal\Core\Session|AccountInterface $current_user
   *   The current user.
   * @param \Drupal\Core\Entity\EntityManager $entity_manager
   *   The entity manager service.
   * @param \Drupal\rest\LinkManager\LinkManagerInterface $link_manager
   *   The link manager service.
   */
  public function __construct(Serializer $serializer, ResourcePluginManager $resource_plugin_manager, AccountInterface $current_user, EntityManager $entity_manager, LinkManagerInterface $link_manager) {
    $this->serializer = $serializer;
    $this->resourcePluginManager = $resource_plugin_manager;
    $this->entityManager = $entity_manager;
    $this->linkManager = $link_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function importContent($module) {
    $created = array();
    $folder = drupal_get_path('module', $module) . "/content";

    if (file_exists($folder)) {
      $file_map = array();
      foreach ($this->entityManager->getDefinitions() as $entity_type_id => $entity_type) {
        $reflection = new \ReflectionClass($entity_type->getClass());
        // We are only interested in importing content entities.
        if ($reflection->implementsInterface('\Drupal\Core\Config\Entity\ConfigEntityInterface')) {
          continue;
        }
        if (!file_exists($folder . '/' . $entity_type_id)) {
          continue;
        }
        $files = $this->scanner()->scan($folder . '/' . $entity_type_id);
        // Default content uses drupal.org as domain.
        // @todo Make this use a uri like default-content:.
        $this->linkManager->setLinkDomain(static::LINK_DOMAIN);
        // Parse all of the files and sort them in order of dependency.
        foreach ($files as $file) {
          $contents = $this->parseFile($file);
          // Decode the file contents.
          $decoded = $this->serializer->decode($contents, 'hal_json');
          // Get the link to this entity.
          $self = $decoded['_links']['self']['href'];

          // Throw an exception when this URL already exists.
          if (isset($file_map[$self])) {
            $args = array(
              '@href' => $self,
              '@first' => $file_map[$self]->uri,
              '@second' => $file->uri,
            );
            // Reset link domain.
            $this->linkManager->setLinkDomain(FALSE);
            throw new \Exception(SafeMarkup::format('Default content with href @href exists twice: @first @second', $args));
          }

          // Store the entity type with the file.
          $file->entity_type_id = $entity_type_id;
          // Store the file in the file map.
          $file_map[$self] = $file;
          // Create a vertex for the graph.
          $vertex = $this->getVertex($self);
          $this->graph[$vertex->link]['edges'] = [];
          if (empty($decoded['_embedded'])) {
            // No dependencies to resolve.
            continue;
          }
          // Here we need to resolve our dependencies;
          foreach ($decoded['_embedded'] as $embedded) {
            foreach ($embedded as $item) {
              $edge = $this->getVertex($item['_links']['self']['href']);
              $this->graph[$vertex->link]['edges'][$edge->link] = TRUE;
            }
          }
        }
      }

      // @todo what if no dependencies?
      $sorted = $this->sortTree($this->graph);
      foreach ($sorted as $link => $details) {
        if (!empty($file_map[$link])) {
          $file = $file_map[$link];
          $entity_type_id = $file->entity_type_id;
          $resource = $this->resourcePluginManager->getInstance(array('id' => 'entity:' . $entity_type_id));
          $definition = $resource->getPluginDefinition();
          $contents = $this->parseFile($file);
          $class = $definition['serialization_class'];
          $entity = $this->serializer->deserialize($contents, $class, 'hal_json', array('request_method' => 'POST'));
          $entity->enforceIsNew(TRUE);
          $entity->save();
          $created[] = $entity;
        }
      }
    }
    // Reset the tree.
    $this->resetTree();
    // Reset link domain.
    $this->linkManager->setLinkDomain(FALSE);
    return $created;
  }

  /**
   * {@inheritdoc}
   */
  public function exportContent($entity_type_id, $entity_id) {
    $storage = $this->entityManager->getStorage($entity_type_id);
    $entity = $storage->load($entity_id);

    $this->linkManager->setLinkDomain(static::LINK_DOMAIN);
    $return = $this->serializer->serialize($entity, 'hal_json', ['json_encode_options' => JSON_PRETTY_PRINT]);
    // Reset link domain.
    $this->linkManager->setLinkDomain(FALSE);
    return $return;
  }

  /**
   * {@inheritdoc}
   */
  public function exportContentWithReferences($entity_type_id, $entity_id) {
    $storage = $this->entityManager->getStorage($entity_type_id);
    $entity = $storage->load($entity_id);

    if (!$entity) {
      throw new \InvalidArgumentException(SafeMarkup::format('Entity @type with ID @id does not exist', ['@type' => $entity_type_id, '@id' => $entity_id]));
    }

    /** @var \Drupal\Core\Entity\ContentEntityInterface[] $entities */
    $entities = [$entity];

    $entities = array_merge($entities, $this->getEntityReferencesRecursive($entity));

    $serialized_entities_per_type = [];
    $this->linkManager->setLinkDomain(static::LINK_DOMAIN);
    // Serialize all entities and key them by entity TYPE and uuid.
    foreach ($entities as $entity) {
      $serialized_entities_per_type[$entity->getEntityTypeId()][$entity->uuid()] = $this->serializer->serialize($entity, 'hal_json', ['json_encode_options' => JSON_PRETTY_PRINT]);
    }
    $this->linkManager->setLinkDomain(FALSE);

    return $serialized_entities_per_type;
  }

  /**
   * Returns all referenced entities of an entity.
   *
   * This method is also recursive to support usecases like a node -> media
   * -> file.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity.
   * @param int $depth
   *   Guard against infinite recursion.
   *
   * @return \Drupal\Core\Entity\EntityInterface[]
   */
  protected function getEntityReferencesRecursive(ContentEntityInterface $entity, $depth = 0) {
    $entity_dependencies = $entity->referencedEntities();

    foreach ($entity_dependencies as $id => $dependent_entity) {
      // Config entities should not be exported but rather provided by default
      // config.
      if ($dependent_entity instanceof ConfigEntityInterface) {
        unset($entity_dependencies[$id]);
      }
      else {
        $entity_dependencies = array_merge($entity_dependencies, $this->getEntityReferencesRecursive($dependent_entity, $depth + 1));
      }
    }

    // Build in some support against infinite recursion.
    if ($depth > 5) {
      return $entity_dependencies;
    }

    return array_unique($entity_dependencies, SORT_REGULAR);
  }

  /**
   * Utility to get a default content scanner
   *
   * @return \Drupal\default_content\DefaultContentScanner
   *   A system listing implementation.
   */
  protected function scanner() {
    if ($this->scanner) {
      return $this->scanner;
    }
    return new DefaultContentScanner();
  }

  /**
   * {@inheritdoc}
   */
  public function setScanner(DefaultContentScanner $scanner) {
    $this->scanner = $scanner;
  }

  /**
   * Parses content files
   */
  protected function parseFile($file) {
    return file_get_contents($file->uri);
  }

  protected function resetTree() {
    $this->graph = [];
    $this->vertexes = array();
  }

  protected function sortTree(array $graph) {
    $graph_object = new Graph($graph);
    $sorted = $graph_object->searchAndSort();
    uasort($sorted, 'Drupal\Component\Utility\SortArray::sortByWeightElement');
    return array_reverse($sorted);
  }

  /**
   * Returns a vertex object for a given item link.
   *
   * Ensures that the same object is returned for the same item link.
   *
   * @param string $item_link
   *   The item link as a string.
   *
   * @return object
   *   The vertex object.
   */
  protected function getVertex($item_link) {
    if (!isset($this->vertexes[$item_link])) {
      $this->vertexes[$item_link] = (object) array('link' => $item_link);
    }
    return $this->vertexes[$item_link];
  }

}
