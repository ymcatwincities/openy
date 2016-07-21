<?php

namespace Drupal\ymca_mappings;

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
   * Query.
   *
   * @var QueryInterface
   */
  protected $query;

  /**
   * MappingRepository constructor.
   *
   * @param QueryFactory $query_factory
   *   Query factory.
   */
  public function __construct(QueryFactory $query_factory) {
    $this->query = $query_factory->get('mapping');
  }

  /**
   * Find mapping by Location Id.
   *
   * @param int $id
   *   Location Id.
   *
   * @return mixed
   *   Location mapping object.
   */
  public function findByLocationId($id) {
    $mapping_id = $this->query
      ->condition('type', self::TYPE)
      ->condition('field_location_ref.target_id', $id)
      ->execute();
    $mapping_id = reset($mapping_id);
    if ($mapping_id) {
      return Mapping::load($mapping_id);
    }
  }

}
