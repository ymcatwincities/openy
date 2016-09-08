<?php

namespace Drupal\ymca_mappings;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryFactory;
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
    $location_mindbody = FALSE;
    $location_mapping = $this->findByLocationPersonifyBranchCode($id);
    if (is_array($location_mapping)) {
      $location_mapping = reset($location_mapping);
    }
    if (!empty($location_mapping->field_mindbody_id->getValue())) {
      return $location_mapping->field_mindbody_id->getValue()[0]['value'];
    }

    return $location_mindbody;
  }

}
