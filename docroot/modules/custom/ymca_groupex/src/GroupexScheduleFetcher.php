<?php
/**
 * @file
 * Fetches schedules.
 */

namespace Drupal\ymca_groupex;

/**
 * Fetches and prepares Groupex data.
 *
 * @package Drupal\ymca_groupex.
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
   * Filtered data (enriched).
   *
   * @var array
   */
  private $filteredData = [];

  /**
   * Query parameters.
   *
   * @var array
   */
  private $parameters = [];

  /**
   * ScheduleFetcher constructor.
   */
  public function __construct(array $parameters) {
    $this->prepareParameters($parameters);
    $this->getData();
    $this->enrichData();
    $this->filterData();
  }

  /**
   * Get schedule.
   *
   * @return array
   *   A schedule. It could be of 2 types:
   *    - day: all classes within classes key
   *    - week: all classes grouped by day within days key
   */
  public function getSchedule() {
    // Prepare classes items.
    $items = [];

    foreach ($this->filteredData as $item) {
      $items[$item->id] = [
        '#theme' => 'groupex_class',
        '#class' => [
          'id' => $item->id,
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
      $schedule['classes'] = [];
      foreach ($items as $id => $class) {
        $schedule['classes'][] = $class;
      }
    }
    else {
      // Pack classes into days.
      $schedule['days'] = [];
      foreach ($items as $id => $class) {
        $schedule['days'][$this->enrichedData[$id]->day][] = $class;
      }
    }

    return $schedule;
  }

  /**
   * Fetch data from the server.
   *
   * @todo The server return extra results for the day before of provided period.
   */
  private function getData() {
    // No request parameters - no data.
    if (empty($this->parameters)) {
      $this->rawData = [];
      return;
    }

    $options = [
      'query' => [
        'schedule' => TRUE,
        'desc' => 'true',
        'location' => $this->parameters['location'],
      ],
    ];

    // Category is optional.
    if ($this->parameters['category'] !== 'any') {
      $options['query']['category'] = $this->parameters['category'];
    }

    // Class is optional.
    if ($this->parameters['class'] !== 'any') {
      $options['query']['class'] = self:: $id_strip . $this->parameters['class'];
    }

    // Filter by date.
    $period = 60 * 60 * 24;
    if ($this->parameters['filter_length'] == 'week') {
      $period = 60 * 60 * 24 * 7;
    }
    $dt = new \DateTime();
    $date = $dt->createFromFormat(self::$date_filter_format, $this->parameters['filter_date']);
    $date->setTime(1, 0, 0);
    $options['query']['start'] = $date->getTimestamp();
    $options['query']['end'] = $date->getTimestamp() + $period;

    $data = $this->request($options);

    $raw_data = [];
    foreach ($data as $item) {
      $raw_data[$item->id] = $item;
    }
    $this->rawData = $raw_data;
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

      // Get time of day.
      $datetime = new \DateTime($item->start);
      $start_hour = $datetime->format('G');
      $item->time_of_day = 'morning';
      $item->time_of_day = ($start_hour >= self::$time_afternoon) ? "afternoon" : $item->time_of_day;
      $item->time_of_day = ($start_hour >= self::$time_evening) ? "evening" : $item->time_of_day;
    }

    $this->enrichedData = $data;
  }

  /**
   * Filter the enriched data.
   */
  private function filterData() {
    $filtered = $this->enrichedData;
    $param = $this->parameters;

    // Filter out by time of the day.
    if (!empty($param['time_of_day'])) {
      $filtered = array_filter($filtered, function($item) use ($param) {
        if (in_array($item->time_of_day, $param['time_of_day'])) {
          return TRUE;
        }
        return FALSE;
      });
    }

    $this->filteredData = $filtered;
  }

  /**
   * Process parameters.
   *
   * @param array $parameters
   *   Input parameters.
   */
  private function prepareParameters($parameters) {
    $this->parameters = $parameters;
  }

}
