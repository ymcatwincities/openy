<?php
/**
 * @file
 * Fetches schedules.
 */

namespace Drupal\ymca_groupex;

/**
 * Fetches and prepares Groupex data.
 * @package Drupal\ymca_groupex
 */
class GroupexScheduleFetcher {

  use GroupexRequestTrait;

  /**
   * Fetched data.
   *
   * @var array
   */
  private $data = [];

  /**
   * Query parameters.
   *
   * @var array
   */
  private $parameters = [];

  /**
   * Fetch data from the server.
   */
  private function getData() {
    $data = [];
//    $options = [
//      'query' => [
//        'schedule' => TRUE,
//        'location' => 26,
//        'start/end' => 1452474000,
//        'category' => 410,
//      ]
//    ];
//
//    return $this->request($options);
    $this->data = $data;
  }

  /**
   * Get schedule.
   *
   * @return array
   *   A list of classes, ready to use.
   */
  public function getSchedule() {
    return [
      [
        'name' => 'Group Cycle',
        'group' => 'Cardio',
        'description' => 'Here class description...',
        'address_1' => 'Studio 1 with Amber S',
        'address_2' => 'Andover',
        'time' => 'Mon 5:15am',
        'duration' => '60 min',
      ],
    ];
  }

  /**
   * ScheduleFetcher constructor.
   */
  public function __construct(array $parameters) {
    $this->parameters = $parameters;
    $this->getData();
  }
}