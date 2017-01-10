<?php

namespace Drupal\default_content;

use Drupal\Component\Graph\Graph;
use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Extension\InfoParserInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\default_content\Event\DefaultContentEvents;
use Drupal\default_content\Event\ExportEvent;
use Drupal\default_content\Event\ImportEvent;
use Drupal\rest\LinkManager\LinkManagerInterface;
use Drupal\rest\Plugin\Type\ResourcePluginManager;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
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
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityManager;

  /**
   * The entity repository.
   *
   * @var \Drupal\Core\Entity\EntityRepositoryInterface
   */
  protected $entityRepository;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The info file parser.
   *
   * @var \Drupal\Core\Extension\InfoParserInterface
   */
  protected $infoParser;

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
   * The event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * Constructs the default content manager.
   *
   * @param \Symfony\Component\Serializer\Serializer $serializer
   *   The serializer service.
   * @param \Drupal\rest\Plugin\Type\ResourcePluginManager $resource_plugin_manager
   *   The rest resource plugin manager.
   * @param \Drupal\Core\Session|AccountInterface $current_user
   *   The current user.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_manager
   *   The entity type manager service.
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository
   *   The entity repository service.
   * @param \Drupal\rest\LinkManager\LinkManagerInterface $link_manager
   *   The link manager service.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   The event dispatcher.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Extension\InfoParserInterface $info_parser
   *   The info file parser.
   */
  public function __construct(Serializer $serializer, ResourcePluginManager $resource_plugin_manager, AccountInterface $current_user, EntityTypeManagerInterface $entity_manager, EntityRepositoryInterface $entity_repository, LinkManagerInterface $link_manager, EventDispatcherInterface $event_dispatcher, ModuleHandlerInterface $module_handler, InfoParserInterface $info_parser) {
    $this->serializer = $serializer;
    $this->resourcePluginManager = $resource_plugin_manager;
    $this->entityManager = $entity_manager;
    $this->entityRepository = $entity_repository;
    $this->linkManager = $link_manager;
    $this->eventDispatcher = $event_dispatcher;
    $this->moduleHandler = $module_handler;
    $this->infoParser = $info_parser;
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
          $item_uuid = $decoded['uuid'][0]['value'];

          // Throw an exception when this UUID already exists.
          if (isset($file_map[$item_uuid])) {
            $args = array(
              '@uuid' => $item_uuid,
              '@first' => $file_map[$item_uuid]->uri,
              '@second' => $file->uri,
            );
            // Reset link domain.
            $this->linkManager->setLinkDomain(FALSE);
            throw new \Exception(new FormattableMarkup('Default content with uuid @uuid exists twice: @first @second', $args));
          }

          // Store the entity type with the file.
          $file->entity_type_id = $entity_type_id;
          // Store the file in the file map.
          $file_map[$item_uuid] = $file;
          // Create a vertex for the graph.
          $vertex = $this->getVertex($item_uuid);
          $this->graph[$vertex->id]['edges'] = [];
          if (empty($decoded['_embedded'])) {
            // No dependencies to resolve.
            continue;
          }
          // Here we need to resolve our dependencies:
          foreach ($decoded['_embedded'] as $embedded) {
            foreach ($embedded as $item) {
              $uuid = $item['uuid'][0]['value'];
              $edge = $this->getVertex($uuid);
              $this->graph[$vertex->id]['edges'][$edge->id] = TRUE;
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
          $created[$entity->uuid()] = $entity;
        }
      }
      $this->eventDispatcher->dispatch(DefaultContentEvents::IMPORT, new ImportEvent($created, $module));
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
    $this->eventDispatcher->dispatch(DefaultContentEvents::EXPORT, new ExportEvent($entity));

    return $return;
  }

  /**
   * {@inheritdoc}
   */
  public function exportContentWithReferences($entity_type_id, $entity_id) {
    $storage = $this->entityManager->getStorage($entity_type_id);
    $entity = $storage->load($entity_id);

    if (!$entity) {
      throw new \InvalidArgumentException(new FormattableMarkup('Entity @type with ID @id does not exist', ['@type' => $entity_type_id, '@id' => $entity_id]));
    }
    if (!($entity instanceof ContentEntityInterface)) {
      throw new \InvalidArgumentException(new FormattableMarkup('Entity @type with ID @id should be a content entity', ['@type' => $entity_type_id, '@id' => $entity_id]));
    }

    $entities = [$entity->uuid() => $entity];
    $entities = $this->getEntityReferencesRecursive($entity, 0, $entities);

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
   * {@inheritdoc}
   */
  public function exportModuleContent($module_name) {
    $info_file = $this->moduleHandler->getModule($module_name)->getPathname();
    $info = $this->infoParser->parse($info_file);
    $exported_content = [];
    if (empty($info['default_content'])) {
      return $exported_content;
    }
    foreach ($info['default_content'] as $entity_type => $uuids) {
      foreach ($uuids as $uuid) {
        $entity = $this->entityRepository->loadEntityByUuid($entity_type, $uuid);
        $exported_content[$entity_type][$uuid] = $this->exportContent($entity_type, $entity->id());
      }
    }
    return $exported_content;
  }

  /**
   * {@inheritdoc}
   */
  public function writeDefaultContent($serialized_by_type, $folder) {
    foreach ($serialized_by_type as $entity_type => $serialized_entities) {
      // Ensure that the folder per entity type exists.
      $entity_type_folder = "$folder/$entity_type";
      file_prepare_directory($entity_type_folder, FILE_CREATE_DIRECTORY);
      foreach ($serialized_entities as $uuid => $serialized_entity) {
        file_put_contents($entity_type_folder . '/' . $uuid . '.json', $serialized_entity);
      }
    }
  }

  /**
   * Returns all referenced entities of an entity.
   *
   * This method is also recursive to support use-cases like a node -> media
   * -> file.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity.
   * @param int $depth
   *   Guard against infinite recursion.
   * @param \Drupal\Core\Entity\ContentEntityInterface[] $indexed_dependencies
   *   Previously discovered dependencies.
   *
   * @return \Drupal\Core\Entity\ContentEntityInterface[]
   *   Keyed array of entities indexed by entity type and ID.
   */
  protected function getEntityReferencesRecursive(ContentEntityInterface $entity, $depth = 0, array &$indexed_dependencies = []) {
    $entity_dependencies = $entity->referencedEntities();

    foreach ($entity_dependencies as $dependent_entity) {
      // Config entities should not be exported but rather provided by default
      // config.
      if (!($dependent_entity instanceof ContentEntityInterface)) {
        continue;
      }
      // Using UUID to keep dependencies unique to prevent recursion.
      $key = $dependent_entity->uuid();
      if (isset($indexed_dependencies[$key])) {
        // Do not add already indexed dependencies.
        continue;
      }
      $indexed_dependencies[$key] = $dependent_entity;
      // Build in some support against infinite recursion.
      if ($depth < 6) {
        // @todo Make $depth configurable.
        $indexed_dependencies += $this->getEntityReferencesRecursive($dependent_entity, $depth + 1, $indexed_dependencies);
      }
    }

    return $indexed_dependencies;
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
      $this->vertexes[$item_link] = (object) array('id' => $item_link);
    }
    return $this->vertexes[$item_link];
  }

}
