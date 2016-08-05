<?php

namespace Drupal\ymca_retention;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\taxonomy\TermStorage;
use Drupal\ymca_mappings\Entity\Mapping;
use Drupal\ymca_mappings\LocationMappingRepository;
use Drupal\ymca_retention\Entity\Member;

/**
 * Defines a leaderboard manager service.
 */
class LeaderboardManager implements LeaderboardManagerInterface {

  /**
   * The config factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity query factory.
   *
   * @var \Drupal\Core\Entity\Query\QueryFactory
   */
  protected $queryFactory;

  /**
   * The cache backend.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface;
   */
  protected $cache;

  /**
   * The location mapping repository.
   *
   * @var \Drupal\ymca_mappings\LocationMappingRepository;
   */
  protected $locationRepository;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\Query\QueryFactory $query_factory
   *   The entity query factory.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   The cache backend.
   * @param \Drupal\ymca_mappings\LocationMappingRepository $location_repository
   *   The location mapping repository.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    EntityTypeManagerInterface $entity_type_manager,
    QueryFactory $query_factory,
    CacheBackendInterface $cache,
    LocationMappingRepository $location_repository
  ) {
    $this->configFactory = $config_factory;
    $this->entityTypeManager = $entity_type_manager;
    $this->queryFactory = $query_factory;
    $this->cache = $cache;
    $this->locationRepository = $location_repository;
  }

  /**
   * {@inheritdoc}
   */
  public function getLeaderboard($branch_id = 0) {
    // Try first to load from cache.
    if ($cache = $this->cache->get('leaderboard:' . $branch_id)) {
      $leaderboard = $cache->data;

      return $leaderboard;
    }

    // Prepare taxonomy data.
    /** @var TermStorage $term_storage */
    $term_storage = $this->entityTypeManager->getStorage('taxonomy_term');
    $parents = $term_storage->loadTree('ymca_retention_activities', 0, 1);
    foreach ($parents as $parent) {
      $parent->children_ids = [];
      $children = $term_storage->loadTree('ymca_retention_activities', $parent->tid, 1);
      foreach ($children as $child) {
        $parent->children_ids[] = $child->tid;
      }
    }

    $member_ids = $this->queryFactory->get('ymca_retention_member')
      ->condition('branch', $branch_id)
      ->execute();
    $members = $this->entityTypeManager->getStorage('ymca_retention_member')
      ->loadMultiple($member_ids);

    $leaderboard = [];
    /** @var Member $member */
    foreach ($members as $rank => $member) {
      $activities = [];
      foreach ($parents as $parent) {
        $activities_ids = $this->queryFactory->get('ymca_retention_member_activity')
          ->condition('member', $member->id())
          ->condition('activity_type', $parent->children_ids, 'IN')
          ->execute();
        $activities[] = count($activities_ids);
      }

      $leaderboard[] = [
        'rank' => $rank,
        'first_name' => $member->getFirstName(),
        'last_name' => substr($member->getLastName(), 0, 1),
        'membership_id' => substr($member->getMemberId(), -4),
        'activities' => $activities,
        'visits' => (int) $member->getVisits(),
      ];
    }

    $this->cache->set('leaderboard:' . $branch_id, $leaderboard, REQUEST_TIME + 6 * 60 * 60);
    return $leaderboard;
  }

  /**
   * {@inheritdoc}
   */
  public function getMemberBranches() {
    // Find out unique branch ids among all the members.
    $branches = $this->queryFactory->getAggregate('ymca_retention_member')
      ->groupBy('branch')
      ->aggregate('id', 'COUNT')
      ->execute();

    $settings = $this->configFactory->get('ymca_retention.branches_settings');
    $excluded_branches = $settings->get('excluded_branches');
    $branch_ids = [];
    foreach ($branches as $branch) {
      if (in_array($branch['branch'], $excluded_branches)) {
        continue;
      }
      $branch_ids[] = $branch['branch'];
    }

    return $branch_ids;
  }

  /**
   * {@inheritdoc}
   */
  public function getMemberLocations() {
    $branch_ids = $this->getMemberBranches();

    $locations = $this->locationRepository->findByLocationPersonifyBranchCode($branch_ids);

    return $locations;
  }

  /**
   * {@inheritdoc}
   */
  public function getLocationsList($none = TRUE) {
    $locations = $this->getMemberLocations();

    $locations_list = [];
    if ($none) {
      $locations_list[] = [
        'branch_id' => 0,
        'name' => t('Select location...'),
      ];
    }
    /** @var Mapping $location */
    foreach ($locations as $location) {
      $locations_list[] = [
        'branch_id' => $location->get('field_location_personify_brcode')->value,
        'name' => $location->getName(),
      ];
    }

    return $locations_list;
  }

}
