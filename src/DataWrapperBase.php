<?php

namespace Drupal\openy_calc;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Render\RendererInterface;
use Drupal\daxko\DaxkoClientInterface;
use Drupal\openy_mappings\LocationMappingRepository;

/**
 * Class DataWrapperBase.
 */
abstract class DataWrapperBase implements DataWrapperInterface {

  /**
   * Query factory.
   *
   * @var \Drupal\Core\Entity\Query\QueryFactory
   */
  protected $queryFactory;

  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * Daxko client.
   *
   * @var \Drupal\daxko\DaxkoClientInterface
   */
  protected $daxkoClient;

  /**
   * Cache backend.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cacheBackend;

  /**
   * Location mapping repository.
   *
   * @var \Drupal\openy_mappings\LocationMappingRepository
   */
  protected $locationRepo;

  /**
   * DataWrapperBase constructor.
   *
   * @param \Drupal\Core\Entity\Query\QueryFactory $queryFactory
   *   Query factory.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   Renderer.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Entity type manager.
   * @param \Drupal\daxko\DaxkoClientInterface $daxkoClient
   *   Daxko client.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cacheBackend
   *   Cache backend.
   * @param \Drupal\openy_mappings\LocationMappingRepository $locationRepo
   *   Location mapping repository.
   */
  public function __construct(QueryFactory $queryFactory, RendererInterface $renderer, EntityTypeManagerInterface $entityTypeManager, DaxkoClientInterface $daxkoClient, CacheBackendInterface $cacheBackend, LocationMappingRepository $locationRepo) {
    $this->queryFactory = $queryFactory;
    $this->renderer = $renderer;
    $this->entityTypeManager = $entityTypeManager;
    $this->daxkoClient = $daxkoClient;
    $this->cacheBackend = $cacheBackend;
    $this->locationRepo = $locationRepo;
  }

  /**
   * {@inheritdoc}
   */
  abstract public function getMembershipPriceMatrix();

  /**
   * Get list of membership types.
   *
   * @return array
   *   The list of membership types keyed by type ID.
   */
  public function getMembershipTypes() {
    $types = [];

    foreach ($this->getMembershipPriceMatrix() as $membership_type) {
      $types[$membership_type['id']] = [
        'title' => $membership_type['title'],
        'description' => $membership_type['description'],
      ];
    }

    return $types;
  }

  /**
   * Get the list of locations.
   *
   * @return array
   *   The list of locations keyed by location ID.
   */
  public function getLocations() {
    $data = [];

    $location_ids = $this->queryFactory->get('node')
      ->condition('type', 'branch')
      ->execute();

    if (!$location_ids) {
      return [];
    }

    $storage = $this->entityTypeManager->getStorage('node');
    $locations = $storage->loadMultiple($location_ids);

    foreach ($locations as $location) {
      $data[$location->id()] = [
        'title' => $location->label(),
      ];
    }

    return $data;
  }

  /**
   * Get price.
   *
   * @param int $location_id
   *   Location ID.
   * @param string $membership_type
   *   Membership type ID.
   *
   * @return string
   *   Price.
   */
  public function getPrice($location_id, $membership_type) {
    foreach ($this->getMembershipPriceMatrix() as $membership_type_item) {
      if ($membership_type_item['id'] == $membership_type) {
        foreach ($membership_type_item['locations'] as $location) {
          if ($location['id'] == $location_id) {
            return $location['price'];
          }
        }
        break;
      }
    }

    return FALSE;
  }

}
