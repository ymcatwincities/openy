<?php

namespace Drupal\ymca_mappings;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\ymca_mappings\Entity\Mapping;
use Drupal\Core\Entity\Query\QueryInterface;

/**
 * Class PersonifyProductMappingRepository.
 */
class PersonifyProductMappingRepository {

  /**
   * Mapping type.
   */
  const TYPE = 'personify_product';

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
   * Load all personify_product mappings.
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

}
