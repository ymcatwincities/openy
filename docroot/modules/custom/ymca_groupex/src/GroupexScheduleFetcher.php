<?php

namespace Drupal\ymca_groupex;

use Drupal\Core\Datetime\DrupalDateTime;

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
   * Processed data (enriched).
   *
   * @var array
   */
  private $processedData = [];

  /**
   * Query parameters.
   *
   * @var array
   */
  private $parameters = [];

  /**
   * Cached schedule.
   *
   * @var array
   */
  private $schedule = [];

  /**
   * Timezone.
   *
   * @var \DateTimeZone
   */
  private $timezone = NULL;

  /**
   * ScheduleFetcher constructor.
   */
  public function __construct() {
    $this->timezone = new \DateTimeZone(\Drupal::config('system.date')->get('timezone')['default']);
    $parameters = \Drupal::request()->query->all();

    $this->prepareParameters($parameters);
    $this->getData();
    $this->enrichData();
    $this->filterData();
    $this->processData();
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
    // Use cached schedule if already processed.
    if ($this->schedule) {
      return $this->schedule;
    }

    $filter_date = DrupalDateTime::createFromTimestamp($this->parameters['filter_timestamp'], $this->timezone);

    // Prepare classes items.
    $items = [];

    foreach ($this->processedData as $item) {
      $items[$item->id] = [
        '#theme' => 'groupex_class',
        '#class' => [
          'id' => trim($item->id),
          'name' => trim($item->title),
          'group' => trim($item->category),
          'description' => $item->desc,
          'address_1' => $item->address_1,
          'address_2' => trim($item->location),
          'date' => $item->date,
          'time' => $item->start,
          'duration' => sprintf('%d min', trim($item->length)),
        ],
      ];
    }

    // Pack classes into the schedule.
    $schedule = [];

    // There 3 types of schedules.
    // Day: show classes for single day.
    // Week: show classes for week grouped by day.
    // Location: show classes for 1 day grouped by location.
    $schedule['type'] = $this->parameters['filter_length'];
    if (count($this->parameters['location']) > 1) {
      $schedule['type'] = 'location';
    }

    switch ($schedule['type']) {
      case 'day':
        $schedule['classes'] = [];
        foreach ($items as $id => $class) {
          $schedule['classes'][] = $class;
          $schedule['title'] = trim($this->enrichedData[$id]->location);
        }
        // Pass 'View This Week’s PDF' href if some location selected.
        if (!empty($this->parameters['location'])) {
          $l = array_shift($this->parameters['location']);
          $t = $this->parameters['filter_timestamp'];
          $schedule['pdf_href'] = 'http://www.groupexpro.com/ymcatwincities/print.php?font=larger&amp;account=3&amp;l=' . $l . '&amp;c=category&amp;week=' . $t;
        }

        // If no location selected show date instead of title.
        if (empty($this->parameters['location'])) {
          $schedule['title'] = $filter_date->format(GroupexRequestTrait::$dateFullFormat);
        }
        break;

      case 'week':
        $schedule['days'] = [];
        foreach ($items as $id => $class) {
          $schedule['days'][$this->enrichedData[$id]->day][] = $class;
        }
        // Pass 'View This Week’s PDF' href if some location selected.
        if (!empty($this->parameters['location'])) {
          $l = array_shift($this->parameters['location']);
          $t = $this->parameters['filter_timestamp'];
          $schedule['pdf_href'] = 'http://www.groupexpro.com/ymcatwincities/print.php?font=larger&amp;account=3&amp;l=' . $l . '&amp;c=category&amp;week=' . $t;
        }

        // If no location selected show date instead of title.
        if (empty($this->parameters['location'])) {
          $schedule['day'] = $filter_date->format(GroupexRequestTrait::$dateFullFormat);
        }
        break;

      case 'location':
        $schedule['locations'] = [];
        $locations = \Drupal::config('ymca_groupex.mapping')->get('locations_short');
        foreach ($items as $id => $class) {
          $short_location_name = trim($this->enrichedData[$id]->location);
          foreach ($locations as $location) {
            if ($location['name'] == $short_location_name) {
              $l = $location['id'];
            }
          }
          $t = $this->parameters['filter_timestamp'];
          $pdf_href = 'http://www.groupexpro.com/ymcatwincities/print.php?font=larger&amp;account=3&amp;l=' . $l . '&amp;c=category&amp;week=' . $t;
          $schedule['locations'][$short_location_name]['classes'][] = $class;
          $schedule['locations'][$short_location_name]['pdf_href'] = $pdf_href;
        }
        $schedule['filter_date'] = date(GroupexRequestTrait::$dateFullFormat, $this->parameters['filter_timestamp']);
        break;
    }

    $this->schedule = $schedule;
    return $this->schedule;
  }

  /**
   * Fetch data from the server.
   */
  private function getData() {
    $this->rawData = [];

    // No request parameters - no data.
    if (empty($this->parameters)) {
      return;
    }

    // One of the 3 search parameters should be provided:
    // 1. Location.
    // 2. Class name.
    // 3. Category.
    if (
      !isset($this->parameters['location']) &&
      $this->parameters['class'] == 'any' &&
      $this->parameters['category'] == 'any') {
      return;
    }

    $options = [
      'query' => [
        'schedule' => TRUE,
        'desc' => 'true',
        'location' => array_filter($this->parameters['location']),
      ],
    ];

    // Category is optional.
    if ($this->parameters['category'] !== 'any') {
      $options['query']['category'] = $this->parameters['category'];
    }

    // Class is optional.
    if ($this->parameters['class'] !== 'any') {
      $options['query']['class'] = self::$idStrip . $this->parameters['class'];
    }

    // Filter by date.
    $interval = 'P1D';
    if ($this->parameters['filter_length'] == 'week') {
      $interval = 'P1W';
    }
    $date = DrupalDateTime::createFromTimestamp($this->parameters['filter_timestamp'], $this->timezone);

    $options['query']['start'] = $date->getTimestamp();
    $options['query']['end'] = $date->add(new \DateInterval($interval))->getTimestamp();

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
      $item->address_1 = sprintf('%s with %s', trim($item->studio), trim($item->instructor));

      // Get day.
      $item->day = $item->date;

      // Get start and end time.
      preg_match("/(.*)-(.*)/i", $item->time, $output);
      $item->start = $output[1];
      $item->end = $output[2];

      // Get time of day.
      $datetime = new \DateTime($item->start);
      $start_hour = $datetime->format('G');
      $item->time_of_day = 'morning';
      $item->time_of_day = ($start_hour >= self::$timeAfternoon) ? "afternoon" : $item->time_of_day;
      $item->time_of_day = ($start_hour >= self::$timeEvening) ? "evening" : $item->time_of_day;

      // Add timestamp.
      $format = 'l, F j, Y';
      $datetime = DrupalDateTime::createFromFormat($format, $item->date, $this->timezone);
      $datetime->setTime(0, 0, 0);
      $item->timestamp = $datetime->getTimestamp();
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

    // Groupex response have some redundant data. Filter it out.
    if ($param['filter_length'] == 'day') {
      // Filter out by the date. Cut off days before.
      $filtered = array_filter($filtered, function($item) use ($param) {
        if ($item->timestamp >= $param['filter_timestamp']) {
          return TRUE;
        }
        return FALSE;
      });

      // Filter out by the date. Cut off days after.
      $filtered = array_filter($filtered, function($item) use ($param) {
        if ($item->timestamp < ($param['filter_timestamp'] + 60 * 60 * 24)) {
          return TRUE;
        }
        return FALSE;
      });
    }

    $this->filteredData = $filtered;
  }

  /**
   * Process data.
   */
  private function processData() {
    $data = $this->filteredData;

    // Groupex returns invalid date for the first day of the week.
    // Example: tue, 02, Feb; wed, 27, Jan; thu, 28, Jan.
    // So, processing.
    if ($this->parameters['filter_length'] == 'week') {
      // Get current day.
      $date = DrupalDateTime::createFromTimestamp($this->parameters['filter_timestamp'], $this->timezone);
      $current_day = $date->format('N');

      // Search for the day equals current.
      foreach ($data as &$item) {
        $item_date = DrupalDateTime::createFromTimestamp($item->timestamp, $this->timezone);
        if ($current_day == $item_date->format('N')) {
          // Set proper data.
          $item_date->sub(new \DateInterval('P7D'));
          $full_date = $item_date->format(GroupexRequestTrait::$dateFullFormat);
          $item->date = $full_date;
          $item->day = $full_date;
          $item->timestamp = $item_date->format('U');
        }
      }
    }

    $this->processedData = $data;
  }

  /**
   * Process parameters.
   *
   * @param array $parameters
   *   Input parameters.
   */
  private function prepareParameters($parameters) {
    $this->parameters = $parameters;

    // The old site has a habit to provide empty filter_date. Fix it here.
    if (empty($this->parameters['filter_date'])) {
      $date = DrupalDateTime::createFromTimestamp(REQUEST_TIME, $this->timezone);
      $this->parameters['filter_date'] = $date->format(self::$dateFilterFormat);
    }

    // Set timestamp.
    $date = DrupalDateTime::createFromFormat(self::$dateFilterFormat, $this->parameters['filter_date'], $this->timezone);
    $date->setTime(0, 0, 0);
    $this->parameters['filter_timestamp'] = $date->getTimestamp();
  }

  /**
   * Check if results are empty.
   *
   * @return bool
   *   True if schedule is empty, false otherwise.
   */
  public function isEmpty() {
    return empty($this->rawData);
  }

}
