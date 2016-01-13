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
   * Fetched raw data.
   *
   * @var array
   */
  private $rawData = [];

  /**
   * Enriched data.
   *
   * @var array
   */
  private $enrichedData = [];

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
    // No request parameters - no data.
    if (empty($this->parameters)) {
      $this->rawData = [];
      return;
    }

    $options = [
      'query' =>  [
        'schedule' => TRUE,
        'desc' => 'true',
        'location' => $this->parameters['location'],
      ],
    ];

    // Category is optional.
    if ($this->parameters['category'] =! 'any') {
      $options['query']['category'] = $this->parameters['category'];
    }

    $data = $this->request($options);

    // @todo Filter out data by monring, afternoon, evening.
    // @todo Group data by day, if parameter is week.
    // @todo Filter out by class.

    $rawData = [];
    foreach ($data as $item) {
      $rawData[$item->id] = $item;
    }
    $this->rawData = $rawData;
  }

  /**
   * Enriches data.
   */
  private function enrichData() {
    $data = $this->rawData;

    foreach ($data as &$item) {
      // Get address_1.
      $item->address_1 = sprintf('%s with %s', $item->studio, $item->instructor);

      // Get day.
      $item->day = substr($item->date, 0, 3);

      // Get start and end time.
      preg_match("/(.*)-(.*)/i", $item->time, $output);
      $item->start = $output[1];
      $item->end = $output[2];
    }

    $this->enrichedData = $data;
  }

  /**
   * Get schedule.
   *
   * Filter out extra data and prepare variables for templates.
   *
   * @return array
   *   A schedule. It could be of 2 types:
   *    - day: all classes within classes key
   *    - week: all classes grouped by day within days key
   */
  public function getSchedule() {
    // Prepare classes items.
    $items = [];
    foreach ($this->enrichedData as $item) {
      $items[$item->id] = [
        '#theme' => 'groupex_class',
        '#class' => [
          'name' => $item->title,
          'group' => $item->category,
          'description' => $item->desc,
          'address_1' => $item->address_1,
          'address_2' => $item->location,
          'time' => sprintf('%s %s', $item->day, $item->start),
          'duration' => sprintf('%d min', $item->length),
        ],
      ];
    }

    // Pack classes into the schedule.
    $schedule = [];
    $schedule['type'] = $this->parameters['filter_length'];

    if ($schedule['type'] == 'day') {
      // Get schedule for the current day.
      $current_day = date('D');
      foreach ($items as $id => $class) {
        if ($this->enrichedData[$id]->day == $current_day) {
          $schedule['classes'][] = $class;
        }
      }
    }
    else {
      // Pack classes into days.
      foreach ($items as $id => $class) {
        $schedule['days'][$this->enrichedData[$id]->day][] = $class;
      }
    }

    return $schedule;
  }

  /**
   * ScheduleFetcher constructor.
   */
  public function __construct(array $parameters) {
    $this->parameters = $parameters;
    $this->getData();
    $this->enrichData();
  }
}