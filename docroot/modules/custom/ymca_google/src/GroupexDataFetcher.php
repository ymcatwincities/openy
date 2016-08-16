<?php

namespace Drupal\ymca_google;

use Drupal\ymca_groupex\GroupexRequestTrait;


/**
 * Class GroupexDataFetcher.
 *
 * @package Drupal\ymca_groupex
 */
class GroupexDataFetcher implements GroupexDataFetcherInterface {

  use GroupexRequestTrait;

  /**
   * Debug mode.
   *
   * @var bool
   */
  public $debug;

  /**
   * Data wrapper.
   *
   * @var GcalGroupexWrapperInterface
   */
  protected $dataWrapper;

  /**
   * GroupexDataFetcher constructor.
   *
   * @param GcalGroupexWrapperInterface $data_wrapper
   *   Data wrapper.
   */
  public function __construct(GcalGroupexWrapperInterface $data_wrapper) {
    $this->dataWrapper = $data_wrapper;
  }

  /**
   * {@inheritdoc}
   */
  public function fetch(array $args) {
    $schedule = $this->dataWrapper->getSchedule();

    $start = $schedule['steps'][$schedule['current']]['start'];
    $end = $schedule['steps'][$schedule['current']]['end'];

    $options = [
      'query' => [
        'schedule' => TRUE,
        'desc' => 'true',
        'start' => $start,
        'end' => $end,
      ],
    ];

    $data = $this->request($options);
    if ($data) {
      if ($this->debug) {
        // Limit data by 3 items for development.
        $data = array_slice($data, 0, 3);
      }
      $this->dataWrapper->setSourceData($data);
      $this->dataWrapper->setTimeFrame([
        'start' => $start,
        'end' => $end,
      ]);
    }

  }

  /**
   * Get Groupex class by ID.
   *
   * @param string $id
   *   Class ID.
   *
   * @return mixed
   *   Class description item.
   */
  public function getClassById($id) {
    $result = FALSE;

    $options = [
      'query' => [
        'description' => TRUE,
        'id' => $id
      ],
    ];

    $data = $this->request($options);
    if (!empty($data)) {
      $result = reset($data);
    }

    return $result;
  }

}
