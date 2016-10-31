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
    // Debug.
    if (FALSE) {
      $data = [
        (object) [
          'date' => 'Monday, September 26, 2016',
          'time' => '8:20am-9:20am',
          'title' => 'BodyPump',
          'studio' => 'AC',
          'category' => 'Strength',
          'instructor' => 'Petya S',
          'original_instructor' => 'Rick S',
          'sub_instructor' => '',
          'length' => '60',
          'location' => 'Andover',
          'id' => '145945',
          'desc' => '<p>Here long description...</p>',
        ],
        (object) [
          'date' => 'Monday, October 3, 2016',
          'time' => '8:20am-9:20am',
          'title' => 'BodyPump',
          'studio' => 'AC',
          'category' => 'Strength',
          'instructor' => 'Vasya S',
          'original_instructor' => 'Rick S',
          'sub_instructor' => '',
          'length' => '60',
          'location' => 'Andover',
          'id' => '145945',
          'desc' => '<p>Here long description...</p>',
        ],
      ];
      $this->dataWrapper->setSourceData($data);
      return;
    }

    $schedule = $this->dataWrapper->getSchedule();

    $start = $schedule['steps'][$schedule['current']]['start'];
    $end = $schedule['steps'][$schedule['current']]['end'];

    // Get schedule items.
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
    }

    // Always add items from hot time frame.
    $options['query']['start'] = REQUEST_TIME;
    $options['query']['end'] = REQUEST_TIME + GcalGroupexWrapper::HOT_TIME_FRAME;
    $hot = $this->request($options);
    if ($hot) {
      if ($this->debug) {
        // Limit data by 3 items for development.
        $hot = array_slice($hot, 0, 3);
      }
    }

    /* We've made 2 requests. Possibly we've got 2 identical items.
    Filter them before the merge */
    foreach ($hot as $hot_item_id => $hot_item_value) {
      $found = FALSE;
      foreach ($data as $data_item_id => $data_item_value) {
        if ($hot_item_value == $data_item_value) {
          $found = TRUE;
        }
      }

      if (!$found) {
        $data[] = $hot_item_value;
      }
    }

    if ($data) {
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
        'id' => $id,
      ],
    ];

    $data = $this->request($options);
    if (!empty($data)) {
      $result = reset($data);
    }

    return $result;
  }

}
