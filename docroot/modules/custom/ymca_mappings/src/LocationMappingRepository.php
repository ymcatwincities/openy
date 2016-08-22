<?php

namespace Drupal\ymca_mappings;

use Drupal\Core\Entity\EntityInterface;
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
   * MappingRepository constructor.
   *
   * @param QueryFactory $query_factory
   *   Query factory.
   */
  public function __construct(QueryFactory $query_factory) {
    $this->queryFactory = $query_factory;
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

    return Mapping::loadMultiple($mapping_ids);
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
      return Mapping::load($mapping_id);
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
    $mapping_id = $this->queryFactory
      ->get('mapping')
      ->condition('type', self::TYPE)
      ->condition('field_mindbody_id', $id)
      ->execute();
    $mapping_id = reset($mapping_id);
    if ($mapping_id) {
      return Mapping::load($mapping_id);
    }

    return FALSE;
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

    return Mapping::loadMultiple($mapping_ids);
  }

}
