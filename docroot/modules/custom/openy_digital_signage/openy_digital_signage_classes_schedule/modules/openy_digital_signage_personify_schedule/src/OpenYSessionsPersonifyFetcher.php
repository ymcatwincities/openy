<?php

namespace Drupal\openy_digital_signage_personify_schedule;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\openy_digital_signage_personify_schedule\Entity\OpenYClassesPersonifySession;
use Drupal\ymca_mappings\LocationMappingRepository;

/**
 * Fetch data from Personify.
 *
 * @ingroup openy_digital_signage_personify_schedule
 */
class OpenYSessionsPersonifyFetcher implements OpenYSessionsPersonifyFetcherInterface {

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
   * The location repository service.
   *
   * @var \Drupal\ymca_mappings\LocationMappingRepository
   */
  protected $locationRepository;

  /**
   * Creates data fetcher service.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\ymca_mappings\LocationMappingRepository $location_repository
   *   The location repository service.
   */
  public function __construct(ConfigFactoryInterface $config_factory, EntityTypeManagerInterface $entity_type_manager, LocationMappingRepository $location_repository) {
    $this->configFactory = $config_factory;
    $this->entityTypeManager = $entity_type_manager;
    $this->locationRepository = $location_repository;
  }

  /**
   * {@inheritdoc}
   */
  public function fetchLocation($location_id) {
    if (!$data = $this->fetchLocationFeed($location_id)) {
      return;
    }
    if ($ids = $this->checkDeleted($data, $location_id)) {
      $this->removeDeleted($ids);
    }
    $this->processData($data, $location_id);
  }

  /**
   * {@inheritdoc}
   */
  public function fetchLocationFeed($location_id) {
    /* @var \Drupal\ymca_mappings\Entity\Mapping $location */
    $location = $this->locationRepository->load($location_id);
    if (empty($location)) {
      return [];
    }
    $feed = [];

    return $feed;
  }

  /**
   * {@inheritdoc}
   */
  public function getLocations() {
    $locations = $this->configFactory
      ->get('openy_digital_signage_personify_schedule.settings')
      ->get('locations');

    return $locations ?: [];
  }

  /**
   * {@inheritdoc}
   */
  public function fetchAll() {
    $locations = $this->getLocations();
    if (empty($locations)) {
      return;
    }

    // Get schedule items.
    foreach ($locations as $id) {
      $this->fetchLocation($id);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function checkDeleted($feed, $location_id) {
    $to_be_deleted = [];

    $date = new \DateTime();
    $date->setTimestamp(REQUEST_TIME);
    $formatted = $date->format(DATETIME_DATETIME_STORAGE_FORMAT);

    $storage = $this->entityTypeManager->getStorage('openy_ds_classes_personify_session');
    $query = $storage->getQuery()
      ->condition('location', $location_id)
      ->condition('date_time.value', $formatted, '>');

    $ids = $query->execute();

    while ($part = array_splice($ids, 0, 10)) {
      $entities = $storage->loadMultiple($part);
      foreach ($entities as $entity) {
        $id = $entity->personify_id->value;
        if (!isset($feed[$id])) {
          $to_be_deleted[] = $id;
        }
      }
    }

    return $to_be_deleted;
  }

  /**
   * {@inheritdoc}
   */
  public function removeDeleted($ids) {
    while ($part = array_splice($ids, 0, 10)) {
      $storage = $this->entityTypeManager->getStorage('openy_ds_classes_personify_session');
      if (!$entities = $storage->loadMultiple($part)) {
        continue;
      }
      $storage->delete($entities);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function processData(array $data, $location_id) {
    /* @var \Drupal\ymca_mappings\Entity\Mapping $location */
    $location = $this->locationRepository->load($location_id);
    $entity_manager = $this->entityTypeManager->getStorage('openy_ds_classes_personify_session');
    foreach ($data as $item) {
      $entity = $entity_manager->loadByProperties(['personify_id' => $item->id]);
      if (is_array($entity)) {
        $entity = reset($entity);
      }
      /* @var OpenYClassesPersonifySession $entity */
      if (empty($entity)) {
        $this->createEntity($item, $location->get('field_location_ref')->target_id);
      }
      elseif ($entity instanceof OpenYClassesPersonifySession) {
        $this->updateEntity($entity, $item);
      }
    }
  }

  /**
   * Create entity for a session from Personify.
   *
   * @param \stdClass $item
   *   Data from Personify.
   * @param int $location
   *   Location id.
   */
  protected function createEntity(\stdClass $item, $location) {
    $json = json_encode($item);
    /* @var OpenYClassesPersonifySession $session */
    $session = $this->entityTypeManager
      ->getStorage('openy_ds_classes_personify_session')
      ->create([
        'personify_id' => $item->id,
        'hash' => md5($json),
        'location' => ['target_id' => $location],
        'title' => $item->title,
        'date_time' => $this->getDateTimeValue($item),
        'studio' => $item->studio,
        'category' => $item->category,
        'instructor' => $item->instructor,
        'original_instructor' => $item->original_instructor,
        'sub_instructor' => $item->sub_instructor,
        'length' => $item->length,
        'description' => $item->desc,
        'raw_data' => $json,
        'canceled' => isset($item->canceled) && $item->canceled == 'true',
      ]);
    $session->save();
  }

  /**
   * Update entity for a session from Personify.
   *
   * @param OpenYClassesPersonifySession $entity
   *   Classes Personify Session to update.
   * @param \stdClass $item
   *   Data from Personify.
   */
  protected function updateEntity(OpenYClassesPersonifySession $entity, \stdClass $item) {
    $json = json_encode($item);
    $hash = md5($json);
    if ($entity->get('hash')->value == $hash) {
      return;
    }
    $entity->set('title', $item->title);
    $entity->set('date_time', $this->getDateTimeValue($item));
    $entity->set('studio', $item->studio);
    $entity->set('category', $item->category);
    $entity->set('instructor', $item->instructor);
    $entity->set('original_instructor', $item->original_instructor);
    $entity->set('sub_instructor', $item->sub_instructor);
    $entity->set('length', $item->length);
    $entity->set('description', $item->desc);
    $entity->set('raw_data', $json);
    $entity->set('hash', $hash);
    $entity->set('canceled', isset($item->canceled) && $item->canceled == 'true');
    $entity->save();
  }

  /**
   * Convert date and time from Personify to Drupal field format.
   *
   * @param \stdClass $item
   *   Data from Personify.
   *
   * @return array
   *   Date and time range.
   */
  protected function getDateTimeValue(\stdClass $item) {
    // @todo  To think in the future about the move this parser out of this class and store raw data from Personify.
    $time = explode('-', $item->time);
    $start_date = new \DateTime($item->date . ' ' . $time[0]);
    $start_date->setTimezone(new \DateTimeZone('UTC'));
    $end_date = new \DateTime($item->date . ' ' . $time[1]);
    $end_date->setTimezone(new \DateTimeZone('UTC'));
    return [
      'value' => $start_date->format('Y-m-d\TH:i:s'),
      'end_value' => $end_date->format('Y-m-d\TH:i:s'),
    ];
  }

}
