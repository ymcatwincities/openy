<?php

namespace Drupal\openy_digital_signage_groupex_schedule;

use Drupal\ymca_groupex\GroupexRequestTrait;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\openy_digital_signage_groupex_schedule\Entity\OpenYClassesGroupExSession;
use Drupal\ymca_mappings\LocationMappingRepository;
use Drupal\ymca_mappings\Entity\Mapping;

/**
 * Fetch data from GroupEx Pro.
 *
 * @ingroup openy_digital_signage_groupex_schedule
 */
class OpenYSessionsGroupExFetcher implements OpenYSessionsGroupExFetcherInterface {

  use GroupexRequestTrait;

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
   * Get default options for a request to GroupEx Pro.
   *
   * @return array
   *   Default options for a request.
   */
  public function defaultOptions() {
    $config = $this->configFactory->get('openy_digital_signage_groupex_schedule.settings');
    $options = [
      'query' => [
        'schedule' => TRUE,
        'desc' => 'true',
        'start' => REQUEST_TIME,
        'end' => strtotime('now +' . $config->get('period') . ' days'),
      ],
    ];
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function fetchLocation($location_id) {
    $options = $this->defaultOptions();

    /* @var \Drupal\ymca_mappings\Entity\Mapping $location */
    $location = $this->locationRepository->load($location_id);
    if (empty($location)) {
      return;
    }
    $options['query']['location'] = $location->get('field_groupex_id')->value;

    $data = $this->request($options);
    if (!empty($data)) {
      $this->processData($data, $location);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function fetchAll() {
    $config = $this->configFactory->get('openy_digital_signage_groupex_schedule.settings');
    $locations = $config->get('locations');
    if (empty($locations)) {
      return;
    }
    // Get schedule items.
    foreach ($locations as $id) {
      $this->fetchLocation($id);
    }
  }

  /**
   * Create or update sessions.
   *
   * @param array $data
   *   Data received from GroupEx Pro.
   * @param \Drupal\ymca_mappings\Entity\Mapping $location
   *   Mapping location entity.
   */
  protected function processData(array $data, Mapping $location) {
    $entity_manager = $this->entityTypeManager->getStorage('openy_ds_classes_groupex_session');
    foreach ($data as $item) {
      $entity = $entity_manager->loadByProperties(['groupex_id' => $item->id]);
      if (is_array($entity)) {
        $entity = reset($entity);
      }
      /* @var OpenYClassesGroupExSession $entity */
      if (empty($entity)) {
        $this->createEntity($item, $location->get('field_location_ref')->target_id);
      }
      elseif ($entity instanceof OpenYClassesGroupExSession) {
        $this->updateEntity($entity, $item);
      }
    }
  }

  /**
   * Create entity for a session from GroupEx Pro.
   *
   * @param \stdClass $item
   *   Data from GroupEx Pro.
   * @param int $location
   *   Location id.
   */
  protected function createEntity(\stdClass $item, $location) {
    $json = json_encode($item);
    /* @var OpenYClassesGroupExSession $session */
    $session = $this->entityTypeManager
      ->getStorage('openy_ds_classes_groupex_session')
      ->create([
        'groupex_id' => $item->id,
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
      ]);
    $session->save();
  }

  /**
   * Update entity for a session from GroupEx Pro.
   *
   * @param OpenYClassesGroupExSession $entity
   *   Classes GroupEx Session to update.
   * @param \stdClass $item
   *   Data from GroupEx Pro.
   */
  protected function updateEntity(OpenYClassesGroupExSession $entity, \stdClass $item) {
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
    $entity->save();
  }

  /**
   * Convert date and time from GroupEx to Drupal field format.
   *
   * @param \stdClass $item
   *   Data from GroupEx Pro.
   *
   * @return array
   *   Date and time range.
   */
  protected function getDateTimeValue(\stdClass $item) {
    // @todo  To think in the future about the move this parser out of this class and store raw data from Groupex.
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
