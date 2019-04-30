<?php

namespace Drupal\openy_pef_gxp_sync\syncer;

use Drupal\Core\State\StateInterface;

/**
 * Class Wrapper.
 *
 * @package Drupal\openy_pef_gxp_sync\syncer.
 */
class Wrapper implements WrapperInterface {

  /**
   * Set 1 to set debug mode.
   */
  const DEBUG_MODE = 0;

  /**
   * Source Data.
   *
   * @var array
   */
  protected $sourceData = [];

  /**
   * Processed data.
   *
   * @var array
   */
  protected $processedData = [];

  /**
   * Hashes.
   *
   * @var array
   */
  protected $hashes = [];

  /**
   * Data to remove.
   *
   * @var array
   */
  protected $dataToRemove = [];

  /**
   * Data to create.
   *
   * @var array
   */
  protected $dataToCreate = [];

  /**
   * State.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * Wrapper constructor.
   *
   * @param \Drupal\Core\State\StateInterface $state
   *   State.
   */
  public function __construct(StateInterface $state) {
    $this->state = $state;
  }

  /**
   * {@inheritdoc}
   */
  public function prepare() {
    $dataToCreate = [];
    $dataToRemove = [];

    $processedData = $this->getProcessedData();

    $hashesSaved = $this->getSavedHashes();
    $hashesCurrent = $this->getCurrentHashes();

    if (!$hashesSaved) {
      // That's initial sync.
      $this->dataToCreate = $processedData;
      return;
    }

    // Use this to test deleted location in the API.
    if (self::DEBUG_MODE && FALSE) {
      unset($hashesCurrent[4]);
    }

    // Use this to test new location in the API.
    if (self::DEBUG_MODE && FALSE) {
      unset($hashesSaved[4]);
    }

    // Use this to test deleted class in the API.
    if (self::DEBUG_MODE && FALSE) {
      unset($hashesCurrent[4][100]);
    }

    $locationsToCompare = array_intersect(array_keys($hashesCurrent), array_keys($hashesSaved));

    // Check if we have extra locations in database.
    $extraLocationsDB = array_diff(array_keys($hashesSaved), array_keys($hashesCurrent));
    // If so, we need to remove all classes in that extra location.
    foreach ($extraLocationsDB as $locationId) {
      $dataToRemove[$locationId] = array_keys($hashesSaved[$locationId]);
    }

    // Check each location to have extra classes in DB.
    foreach ($locationsToCompare as $locationId) {
      $extraClassesDB = array_diff(array_keys($hashesSaved[$locationId]), array_keys($hashesCurrent[$locationId]));
      // If so, we need to remove extra class in location.
      foreach ($extraClassesDB as $classId) {
        $dataToRemove[$locationId][] = $classId;
      }
    }

    // Check if we have extra locations in API.
    $extraLocationsAPI = array_diff(array_keys($hashesCurrent), array_keys($hashesSaved));
    // If so, we need to add all classes in that extra location.
    foreach ($extraLocationsAPI as $locationId) {
      $dataToCreate[$locationId] = $processedData[$locationId];
    }

    // Check each location to have extra classes in API.
    foreach ($locationsToCompare as $locationId) {
      $extraClassesAPI = array_diff(array_keys($hashesCurrent[$locationId]), array_keys($hashesSaved[$locationId]));
      // If so, we need to remove extra class in location.
      foreach ($extraClassesAPI as $classId) {
        $dataToCreate[$locationId][$classId] = $processedData[$locationId][$classId];
      }
    }

    // Check if we have changed classes within locations.
    foreach ($hashesCurrent as $locationId => $locationData) {
      if (array_key_exists($locationId, $hashesSaved)) {

        // Check each class.
        foreach (array_keys($locationData) as $classId) {
          if (array_key_exists($classId, $hashesSaved[$locationId])) {
            if ($hashesCurrent[$locationId][$classId] != $hashesSaved[$locationId][$classId]) {
              $dataToRemove[$locationId][] = $classId;
              $dataToCreate[$locationId][$classId] = $processedData[$locationId][$classId];
            }
          }
        }
      }
    }

    $this->dataToRemove = $dataToRemove;
    $this->dataToCreate = $dataToCreate;
  }

  /**
   * {@inheritdoc}
   */
  public function getSourceData() {
    $data = $this->sourceData;

    // Use this to check if some class changed in the API.
    if (self::DEBUG_MODE && FALSE) {
      $data[4][0]['description'] = "I'm changed!";
    }

    // Use this to check when some class added in the API.
    if (self::DEBUG_MODE && FALSE) {
      $data[4][] = [
        'class_id' => "999999",
        'category' => "Strength",
        'location' => "Andover",
        'title' => "test",
        'description' => "test",
        'start_date' => "September 10, 2012",
        'end_date' => "September 10, 2037",
        'recurring' => "weekly",
        'studio' => "test",
        'instructor' => "test",
        'patterns' => [
          "day" => "Monday",
          "start_time" => "08:20",
          "end_time" => "09:15",
        ],
      ];
    }

    return $data;
  }

  /**
   * {@inheritdoc}
   */
  public function setSourceData($locationId, array $data) {
    if (!isset($this->sourceData[$locationId])) {
      $this->sourceData[$locationId] = [];
    }

    $this->sourceData[$locationId] = array_merge($this->sourceData[$locationId], $data);
  }

  /**
   * {@inheritdoc}
   */
  public function getProcessedData() {
    if (!empty($this->processedData)) {
      return $this->processedData;
    }

    $items = $this->process($this->getSourceData());
    $this->processedData = $items;

    return $items;
  }

  /**
   * Process data & create hashes.
   *
   * @param array $data
   *   Source data.
   *
   * @return array
   *   Processed data.
   */
  private function process(array $data) {
    // Group items at first.
    $grouped = [];
    foreach ($data as $locationId => $locationItems) {
      foreach ($locationItems as $item) {
        $grouped[$locationId][$item['class_id']][] = $item;
      }
    }

    $hashes = [];
    $processed = [];

    foreach ($grouped as $locationId => $locationData) {
      foreach ($locationData as $classId => $classData) {
        $hashes[$locationId][$classId] = (string) md5(serialize($classData));

        foreach ($classData as $class) {
          $this->cleanUpTitle($class);
          $class['location_id'] = $locationId;
          $processed[$locationId][$classId][] = $class;
        }
      }
    }

    $this->hashes = $hashes;

    return $processed;
  }

  /**
   * {@inheritdoc}
   */
  public function getCurrentHashes() {
    if (!empty($this->hashes)) {
      return $this->hashes;
    }

    $this->getProcessedData();
    return $this->hashes;
  }

  /**
   * {@inheritdoc}
   */
  public function getSavedHashes() {
    $data = $this->state->get('openy_pef_gxp_sync_hashes');
    if (!$data) {
      return FALSE;
    }

    return unserialize($data);
  }

  /**
   * {@inheritdoc}
   */
  public function setSavedHashes() {
    $serialized = serialize($this->getCurrentHashes());
    $this->state->set('openy_pef_gxp_sync_hashes', $serialized);
  }

  /**
   * {@inheritdoc}
   */
  public function getDataToRemove() {
    return $this->dataToRemove;
  }

  /**
   * {@inheritdoc}
   */
  public function getDataToCreate() {
    return $this->dataToCreate;
  }

  /**
   * Clean up broken titles.
   *
   * @param array $item
   *   Schedules item.
   */
  private function cleanUpTitle(array &$item) {
    $item['title'] = str_replace('Â', '', $item['title']);
  }

}
