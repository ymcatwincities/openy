<?php

namespace Drupal\openy_digital_signage_personify_schedule;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\openy_digital_signage_personify_schedule\Entity\OpenYClassesPersonifySession;
use Drupal\ymca_mappings\LocationMappingRepository;
use Drupal\ymca_personify\PersonifyApi;

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
  public function getLocations() {
    $locations = $this->configFactory
      ->get('openy_digital_signage_personify_schedule.settings')
      ->get('locations');

    return $locations ?: [];
  }

  /**
   * Get a ist of Personify branch codes.
   *
   * @return array
   *   Personify branch codes.
   */
  public function getLocationBranchCodes() {
    $branches = [];
    $locations = $this->getLocations();
    if (empty($locations)) {
      return $branches;
    }

    // Build a list of Personify branch ids.
    $branches = [];
    foreach ($locations as $id) {
      /* @var \Drupal\ymca_mappings\Entity\Mapping $location */
      $location = $this->locationRepository->load($id);
      if (empty($location)) {
        continue;
      }
      $branches[] = (int) $location->get('field_location_personify_brcode')->value;
    }

    return $branches;
  }

  /**
   * {@inheritdoc}
   */
  public function fetchLocationsFeed($branches) {
    $feed = [];
    $available_date = new \DateTime();
    $available_date->setTime(0, 0, 0);
    $expiration_date = new \DateTime();
    $expiration_date->setTime(0, 0, 0);
    $branch_ids = implode(',', $branches);
    /* @var PersonifyApi $api */
    $api = \Drupal::service('ymca_personify.personify_api');
    /* @var \stdClass $response */
    $response = $api->getProductListing('FW', $available_date, $expiration_date, $branch_ids);

    if (empty($response) || empty($response->ProductListingRecord)) {
      return $feed;
    }

    $date = new \DateTime();
    $date->setTime(0, 0, 0);
    foreach ($response->ProductListingRecord as $product) {
      // If branch not equal to branch from settings then skip.
      if (!in_array((int) $product->Branch, $branches)) {
        continue;
      }
      // If product already expired then skip.
      $expr_date = new \DateTime($product->ExpirationDate);
      if ($date > $expr_date) {
        continue;
      }

      $feed[$product->ProductId] = $product;
    }

    return $feed;
  }

  /**
   * {@inheritdoc}
   */
  public function fetchAll() {
    $branch_codes = $this->getLocationBranchCodes();
    if (empty($branch_codes)) {
      return;
    }

    // Get schedule items.
    if (!$data = $this->fetchLocationsFeed($branch_codes)) {
      return;
    }

    if ($ids = $this->checkDeleted($data)) {
      $this->removeDeleted($ids);
    }
    $this->processData($data);
  }

  /**
   * {@inheritdoc}
   */
  public function checkDeleted($feed) {
    $to_be_deleted = [];

    $date = new \DateTime();
    $date->setTimestamp(REQUEST_TIME);
    $formatted = $date->format(DATETIME_DATETIME_STORAGE_FORMAT);

    $locations = $this->getLocations();
    if (empty($locations)) {
      return $to_be_deleted;
    }

    $storage = $this->entityTypeManager->getStorage('openy_ds_class_personify_session');

    $query = $storage->getQuery()
      ->condition('location', array_values($locations), 'IN')
      ->condition('date.value', $formatted, '<')
      ->condition('date.end_value', $formatted, '>');
    $ids = $query->execute();

    while ($part = array_splice($ids, 0, 10)) {
      $entities = $storage->loadMultiple($part);
      foreach ($entities as $entity) {
        /* @var OpenYClassesPersonifySession $entity */
        $id = $entity->get('personify_id')->value;
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
      $storage = $this->entityTypeManager->getStorage('openy_ds_class_personify_session');
      if (!$entities = $storage->loadMultiple($part)) {
        continue;
      }
      $storage->delete($entities);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function processData(array $data) {
    $entity_manager = $this->entityTypeManager->getStorage('openy_ds_class_personify_session');
    foreach ($data as $item) {
      $entity = $entity_manager->loadByProperties([
        'personify_id' => $item->ProductId,
      ]);
      if (is_array($entity)) {
        $entity = reset($entity);
      }
      /* @var OpenYClassesPersonifySession $entity */
      if (empty($entity)) {
        $locations = $this->locationRepository->findByLocationPersonifyBranchCode($item->Branch);
        /* @var \Drupal\ymca_mappings\Entity\Mapping $location */
        $location = reset($locations);
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
      ->getStorage('openy_ds_class_personify_session')
      ->create([
        'personify_id' => $item->ProductId,
        'hash' => md5($json),
        'location' => ['target_id' => $location],
        'title' => $item->ShortName,
        'date' => [
          'value' => $item->AvailableDate,
          'end_value' => $item->ExpirationDate,
        ],
        'repeat' => $this->getRepeatSettings($item),
        'start_time' => $item->StartTime,
        'end_time' => $item->EndTime,
        'studio' => $item->Room,
        'instructor' => $this->getInstructorName($item),
        'sub_instructor' => '',
        'canceled' => $this->getCanceledStatus($item),
        'raw_data' => $json,
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
    $repeat_settings = $this->getRepeatSettings($item);
    if ($repeat_settings != $entity->get('repeat')->value) {
      // Set marker which is used in the hook_entity_update to identify that
      // sessions have to be recreated.
      $entity->update_repeat = TRUE;
      $entity->set('repeat', $repeat_settings);
    }
    $entity->set('title', $item->ShortName);
    $entity->set('studio', $item->Room);
    $entity->set('date', [
      'value' => $item->AvailableDate,
      'end_value' => $item->ExpirationDate,
    ]);
    $entity->set('start_time', $item->StartTime);
    $entity->set('end_time', $item->EndTime);
    $entity->set('instructor', $this->getInstructorName($item));
    $entity->set('sub_instructor', $this->getSubstituteInstructorName($entity, $item));
    $entity->set('raw_data', $json);
    $entity->set('canceled', $this->getCanceledStatus($item));
    $entity->set('hash', $hash);
    $entity->save();
  }

  /**
   * Find instructor name.
   *
   * @param \stdClass $item
   *   Data from Personify.
   *
   * @return string
   *   Instructor name.
   */
  protected function getInstructorName(\stdClass $item) {
    $instructor = '';
    if (empty($item->RelatedCustomerInformation)) {
      return $instructor;
    }
    $customer_info = reset($item->RelatedCustomerInformation);
    if (empty($customer_info) || empty($customer_info->Evaluation) || $customer_info->Relationship != 'INSTRUCTOR') {
      return $instructor;
    }

    return $customer_info->Evaluation;
  }

  /**
   * Get substitute instructor name.
   *
   * @param \Drupal\openy_digital_signage_personify_schedule\Entity\OpenYClassesPersonifySession $entity
   *   Personify Class entity.
   * @param \stdClass $item
   *   Data from Personify.
   *
   * @return string
   *   Substitute instructor or empty string.
   */
  protected function getSubstituteInstructorName(OpenYClassesPersonifySession $entity, \stdClass $item) {
    $instructor_name = $entity->get('instructor')->value;
    $new_instructor_name = $this->getInstructorName($item);

    return $instructor_name != $new_instructor_name ? $new_instructor_name : '';
  }

  /**
   * Get class repeat settings.
   *
   * @param \stdClass $item
   *   Data from Personify.
   *
   * @return array
   *   Class repeat settings.
   */
  protected function getRepeatSettings(\stdClass $item) {
    $settings = [
      'week_day' => [
        'monday' => $item->MondayFlag,
        'tuesday' => $item->TuesdayFlag,
        'wednesday' => $item->WednesdayFlag,
        'thursday' => $item->ThursdayFlag,
        'friday' => $item->FridayFlag,
        'saturday' => $item->SaturdayFlag,
        'sunday' => $item->SundayFlag,
      ],
    ];

    return serialize($settings);
  }

  /**
   * Get canceled status.
   *
   * @param \stdClass $item
   *   Data from Personify.
   *
   * @return bool
   *   Canceled or not.
   */
  protected function getCanceledStatus(\stdClass $item) {
    return FALSE;
  }

}
