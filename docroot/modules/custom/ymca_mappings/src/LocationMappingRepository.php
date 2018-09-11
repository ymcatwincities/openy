<?php

namespace Drupal\ymca_mappings;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Field\FieldItemList;
use Drupal\ymca_mappings\Entity\Mapping;
use Drupal\Core\Entity\Query\QueryInterface;

/**
 * Class LocationMappingRepository.
 */
class LocationMappingRepository {

  /**
   * Mapping type.
   */
  const TYPE = 'location';

  /**
   * The query factory.
   *
   * @var QueryInterface
   */
  protected $queryFactory;

  /**
   * Mapping storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $storage;

  /**
   * MappingRepository constructor.
   *
   * @param QueryFactory $query_factory
   *   Query factory.
   * @param EntityTypeManagerInterface $entity_type_manager
   *   Entity type manager.
   */
  public function __construct(QueryFactory $query_factory, EntityTypeManagerInterface $entity_type_manager) {
    $this->queryFactory = $query_factory;
    $this->storage = $entity_type_manager->getStorage('mapping');
  }

  /**
   * Load all location mappings.
   *
   * @return array
   *   An array of found location mapping objects sorted by name.
   */
  public function loadAll() {
    $mapping_ids = $this->queryFactory
      ->get('mapping')
      ->condition('type', self::TYPE)
      ->sort('name', 'ASC')
      ->execute();
    if (!$mapping_ids) {
      return [];
    }

    return $this->storage->loadMultiple($mapping_ids);
  }

  /**
   * Loads one entity.
   *
   * @param mixed $id
   *   The ID of the entity to load.
   *
   * @return \Drupal\Core\Entity\EntityInterface|null
   *   An entity object. NULL if no matching entity is found.
   */
  public function load($id) {
    return $this->storage->load($id);
  }

  /**
   * Load all location mappings where GroupEx ID is present.
   *
   * @return array
   *   An array of found location mapping objects sorted by name.
   */
  public function loadAllLocationsWithGroupExId() {
    $mapping_ids = $this->queryFactory
      ->get('mapping')
      ->condition('type', self::TYPE)
      ->condition('field_groupex_id', 0, '>')
      ->sort('name', 'ASC')
      ->execute();
    if (!$mapping_ids) {
      return [];
    }

    return $this->storage->loadMultiple($mapping_ids);
  }

  /**
   * Load all location mappings where Personify branch code is present.
   *
   * @return array
   *   An array of found location mapping objects sorted by name.
   */
  public function loadAllLocationsWithPersonifyId() {
    $mapping_ids = $this->queryFactory
      ->get('mapping')
      ->condition('type', self::TYPE)
      ->condition('field_location_personify_brcode', 0, '>')
      ->sort('name', 'ASC')
      ->execute();
    if (!$mapping_ids) {
      return [];
    }

    return $this->storage->loadMultiple($mapping_ids);
  }

  /**
   * Load all location mappings where MindBody ID is present.
   *
   * @return array
   *   An array of found location mapping objects sorted by name.
   */
  public function loadAllLocationsWithMindBodyId() {
    $mapping_ids = $this->queryFactory
      ->get('mapping')
      ->condition('type', self::TYPE)
      ->condition('field_mindbody_id', 0, '>')
      ->sort('name', 'ASC')
      ->execute();
    if (!$mapping_ids) {
      return [];
    }

    return $this->storage->loadMultiple($mapping_ids);
  }

  /**
   * Return all Groupex location IDs.
   *
   * @return array
   *   Groupex IDs.
   */
  public function loadAllGroupexIds() {
    $mapping_ids = $this->queryFactory
      ->get('mapping')
      ->condition('type', self::TYPE)
      ->sort('name', 'ASC')
      ->execute();
    if (!$mapping_ids) {
      return [];
    }

    $ids = [];

    // Let's save some memory.
    $chunk_size = 100;
    $chunks = array_chunk($mapping_ids, $chunk_size);
    foreach ($chunks as $chunk) {
      $entities = Mapping::loadMultiple($chunk);
      foreach ($entities as $entity) {
        $field_id = $entity->get('field_groupex_id');
        if ($field_id->isEmpty()) {
          continue;
        }
        if ($id = $field_id->get(0)->value) {
          $ids[] = $id;
        }
      }
    }

    return $ids;
  }

  /**
   * Find mapping by Location Id.
   *
   * @param int $id
   *   Location Id.
   *
   * @return mixed
   *   Location mapping object or FALSE if not found.
   */
  public function findByLocationId($id) {
    $mapping_id = $this->queryFactory
      ->get('mapping')
      ->condition('type', self::TYPE)
      ->condition('field_location_ref.target_id', $id)
      ->execute();
    $mapping_id = reset($mapping_id);
    if ($mapping_id) {
      return $this->storage->load($mapping_id);
    }

    return FALSE;
  }

  /**
   * Find mapping by GroupEx Id.
   *
   * @param int $id
   *   Location Id.
   *
   * @return mixed
   *   Location mapping object or FALSE if not found.
   */
  public function findByGroupexId($id) {
    $mapping_id = $this->queryFactory
      ->get('mapping')
      ->condition('type', self::TYPE)
      ->condition('field_groupex_id.value', $id)
      ->execute();
    $mapping_id = reset($mapping_id);
    if ($mapping_id) {
      return $this->storage->load($mapping_id);
    }

    return FALSE;
  }

  /**
   * Find mapping by MindBody ID.
   *
   * @param int $id
   *   MindBody ID.
   *
   * @return EntityInterface|bool
   *   Mapping.
   */
  public function findByMindBodyId($id) {
    $cache = &drupal_static(__FUNCTION__);

    if (!isset($cache[$id])) {
      $result = $this->queryFactory
        ->get('mapping')
        ->condition('type', self::TYPE)
        ->condition('field_mindbody_id', $id)
        ->execute();
      $mapping_id = reset($result);
      $cache[$id] = $this->storage->load($mapping_id);
    }

    return $cache[$id];
  }

  /**
   * Find by Location branch code in Personify.
   *
   * @param mixed $code
   *   Either single code or an array of codes.
   *
   * @return array
   *   An array of found location mapping objects sorted by name.
   */
  public function findByLocationPersonifyBranchCode($code) {
    if (!$code) {
      return [];
    }
    if (!is_array($code)) {
      $code = [$code];
    }

    $mapping_ids = $this->queryFactory
      ->get('mapping')
      ->condition('type', self::TYPE)
      ->condition('field_location_personify_brcode', $code, 'IN')
      ->sort('name', 'ASC')
      ->execute();
    if (!$mapping_ids) {
      return [];
    }

    return $this->storage->loadMultiple($mapping_ids);
  }

  /**
   * Find MindBody LocationID by Personify LocationID.
   *
   * @param int $id
   *   Personify ID.
   *
   * @return int|bool
   *   MindBody ID.
   */
  public function findMindBodyIdByPersonifyId($id) {
    $location_mapping = $this->findByLocationPersonifyBranchCode($id);
    if (is_array($location_mapping)) {
      $location_mapping = reset($location_mapping);
    }
    if ($location_mapping && !empty($location_mapping->field_mindbody_id->getValue())) {
      return $location_mapping->field_mindbody_id->getValue()[0]['value'];
    }

    return FALSE;
  }

  /**
   * Loads one or more entities.
   *
   * @param array $mapping_ids
   *   An array of entity IDs, or NULL to load all entities.
   *
   * @return \Drupal\Core\Entity\EntityInterface[]
   *   An array of entity objects indexed by their IDs. Returns an empty array
   *   if no matching entities are found.
   */
  public function loadMultiple($mapping_ids) {
    return $this->storage->loadMultiple($mapping_ids);
  }

}
