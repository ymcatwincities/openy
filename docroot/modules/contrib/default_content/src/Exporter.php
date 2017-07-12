<?php

namespace Drupal\default_content;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\InfoParserInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\default_content\Event\DefaultContentEvents;
use Drupal\default_content\Event\ExportEvent;
use Drupal\hal\LinkManager\LinkManagerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Serializer\Serializer;

/**
 * A service for handling import of default content.
 *
 * @todo throw useful exceptions
 */
class Exporter implements ExporterInterface {

  /**
   * Defines relation domain URI for entity links.
   *
   * @var string
   */
  protected $linkDomain;

  /**
   * The serializer service.
   *
   * @var \Symfony\Component\Serializer\Serializer
   */
  protected $serializer;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

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
   * The link manager service.
   *
   * @var \Drupal\hal\LinkManager\LinkManagerInterface
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
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository
   *   The entity repository service.
   * @param \Drupal\hal\LinkManager\LinkManagerInterface $link_manager
   *   The link manager service.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   The event dispatcher.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Extension\InfoParserInterface $info_parser
   *   The info file parser.
   * @param string $link_domain
   *   Defines relation domain URI for entity links.
   */
  public function __construct(Serializer $serializer, EntityTypeManagerInterface $entity_type_manager, EntityRepositoryInterface $entity_repository, LinkManagerInterface $link_manager, EventDispatcherInterface $event_dispatcher, ModuleHandlerInterface $module_handler, InfoParserInterface $info_parser, $link_domain) {
    $this->serializer = $serializer;
    $this->entityTypeManager = $entity_type_manager;
    $this->entityRepository = $entity_repository;
    $this->linkManager = $link_manager;
    $this->eventDispatcher = $event_dispatcher;
    $this->moduleHandler = $module_handler;
    $this->infoParser = $info_parser;
    $this->linkDomain = $link_domain;
  }

  /**
   * {@inheritdoc}
   */
  public function exportContent($entity_type_id, $entity_id) {
    $storage = $this->entityTypeManager->getStorage($entity_type_id);
    $entity = $storage->load($entity_id);

    $this->linkManager->setLinkDomain($this->linkDomain);
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
    $storage = $this->entityTypeManager->getStorage($entity_type_id);
    $entity = $storage->load($entity_id);

    if (!$entity) {
      throw new \InvalidArgumentException(sprintf('Entity "%s" with ID "%s" does not exist', $entity_type_id, $entity_id));
    }
    if (!($entity instanceof ContentEntityInterface)) {
      throw new \InvalidArgumentException(sprintf('Entity "%s" with ID "%s" should be a content entity', $entity_type_id, $entity_id));
    }

    $entities = [$entity->uuid() => $entity];
    $entities = $this->getEntityReferencesRecursive($entity, 0, $entities);

    $serialized_entities_per_type = [];
    $this->linkManager->setLinkDomain($this->linkDomain);
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
        if (!$entity) {
          throw new \InvalidArgumentException(sprintf('Entity "%s" with UUID "%s" does not exist', $entity_type, $uuid));
        }
        $exported_content[$entity_type][$uuid] = $this->exportContent($entity_type, $entity->id());
      }
    }
    return $exported_content;
  }

  /**
   * {@inheritdoc}
   */
  public function writeDefaultContent(array $serialized_by_type, $folder) {
    foreach ($serialized_by_type as $entity_type => $serialized_entities) {
      // Ensure that the folder per entity type exists.
      $entity_type_folder = "$folder/$entity_type";
      $this->prepareDirectory($entity_type_folder);
      foreach ($serialized_entities as $uuid => $serialized_entity) {
        $this->putFile($entity_type_folder, $uuid, $serialized_entity);
      }
    }
  }

  /**
   * Helper for ::writeDefaultContent to wrap file_prepare_directory().
   *
   * @param string $path
   *   Content directory + entity directory to prepare.
   */
  protected function prepareDirectory($path) {
    file_prepare_directory($path, FILE_CREATE_DIRECTORY);
  }

  /**
   * Helper for ::writeDefaultContent to wrap file_put_contents.
   *
   * @param string $path
   *   Content directory + entity directory to which to write the file.
   * @param string $uuid
   *   Entity UUID, to be used as filename.
   * @param string $serialized_entity
   *   The serialized entity to write.
   */
  protected function putFile($path, $uuid, $serialized_entity) {
    file_put_contents($path . '/' . $uuid . '.json', $serialized_entity);
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

}
