<?php

namespace Drupal\ymca_mappings;

use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\ymca_mappings\Entity\Mapping;
use Drupal\Core\Entity\Query\QueryInterface;

/**
 * Class LocationMappingRepository.
 */
class TrainerMappingRepository {

  /**
   * Mapping type.
   */
  const TYPE = 'trainer';

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
   * Find trainer mapping by trainer's name.
   *
   * @param string $name
   *   Name.
   *
   * @return mixed
   *   Trainer mapping entity.
   */
  public function findByName($name) {
    $mapping_id = $this->query
      ->condition('type', self::TYPE)
      ->condition('name', $name, 'LIKE')
      ->execute();
    $mapping_id = reset($mapping_id);
    if ($mapping_id) {
      return Mapping::load($mapping_id);
    }
  }

  /**
   * Find trainer mapping by Page Id.
   *
   * @param int $id
   *   ID of trainer's page.
   *
   * @return mixed
   *   Trainer mapping entity.
   */
  public function findByPageId($id) {
    $mapping_id = $this->query
      ->condition('type', self::TYPE)
      ->condition('field_trainer_page_ref.target_id', $id)
      ->execute();
    $mapping_id = reset($mapping_id);
    if ($mapping_id) {
      return Mapping::load($mapping_id);
    }
  }

}
