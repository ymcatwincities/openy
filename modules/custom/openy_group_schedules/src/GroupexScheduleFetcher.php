<?php

namespace Drupal\openy_group_schedules;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Url;
use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * Fetches and prepares GroupEx Pro data.
 *
 * @package Drupal\openy_group_schedules.
 */
class GroupexScheduleFetcher {

  use GroupexRequestTrait;

  /**
   * PDF print uri.
   */
  const PRINT_URI = 'https://www.groupexpro.com/schedule/print.php';

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
   * The groupex helper.
   *
   * @var GroupexHelper
   */
  protected $groupexHelper;

  /**
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * GroupexScheduleFetcher constructor.
   *
   * @param GroupexHelper $groupex_helper
   *   The GroupEx Pro helper.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory.
   * @param null|array $parameters
   *   Parameters.
   */
  public function __construct(GroupexHelper $groupex_helper, ConfigFactoryInterface $config_factory, $parameters = NULL) {
    $this->groupexHelper = $groupex_helper;
    $this->configFactory = $config_factory;

    if (empty($parameters)) {
      $request_query = [];
      $request = \Drupal::request();
      if (!empty($request) && is_a($request, '\Symfony\Component\HttpFoundation\Request')) {
        $request_query = $request->query->all();
      }
      $parameters = $request_query;
    }

    $this->timezone = new \DateTimeZone(\Drupal::config('system.date')->get('timezone')['default']);
    $this->parameters = self::normalizeParameters($parameters);
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
    $request_time = \Drupal::time()->getRequestTime();
    $conf = $this->configFactory->get('openy_group_schedules.settings');
    $days_range = is_numeric($conf->get('days_range')) ? $conf->get('days_range') : 14;

    $filter_date = DrupalDateTime::createFromTimestamp($this->parameters['filter_timestamp'], $this->timezone);
    $current_date = DrupalDateTime::createFromTimestamp($request_time, $this->timezone)->format(GroupexRequestTrait::$dateFilterFormat);
    // Define end date of shown items as 2 weeks.
    $end_date = DrupalDateTime::createFromTimestamp($request_time + 86400 * $days_range, $this->timezone);

    // Prepare classes items.
    $items = [];

    foreach ($this->processedData as $item) {
      // If item date more than 1 week in future skip it.
      $item_date = DrupalDateTime::createFromTimestamp($item->timestamp, $this->timezone);
      if ($item_date > $end_date) {
        continue;
      }
      $class_url_options = $this->parameters;
      // $class_url_options['class'] = $item->class_id; //doesn't exist in API, needs to be added through processData().
      $class_url_options['filter_date'] = $current_date;
      $class_url_options['filter_length'] = 'week';
      $class_url_options['groupex_class'] = 'groupex_table_class_individual';
      $class_url_options['view_mode'] = 'class';
      unset($class_url_options['instructor']);

      $instructor_url_options = $this->parameters;
      $instructor_url_options['filter_date'] = $current_date;
      $instructor_url_options['filter_length'] = 'week';

      $instructor_url_options['instructor'] = $item->instructor;
      // Here we need to remove redundant HTML if exists.
      // Example: Melissa T.<br><span class="fa fa-refresh" aria-hidden="true"></span><span class="sub">Lucy T.</span>
      $pos = strpos($item->instructor, '<span class="sub">');
      if (FALSE !== $pos) {
        $original_instructor = substr_replace($item->instructor, '', 0, $pos);
        $original_instructor = str_replace('<span class="sub">', '', $original_instructor);
        $original_instructor = str_replace('</span>', '', $original_instructor);
        $instructor_url_options['instructor'] = $original_instructor;
      }

      $instructor_url_options['class'] = 'any';
      $instructor_url_options['groupex_class'] = 'groupex_table_instructor_individual';
      unset($instructor_url_options['view_mode']);

      $date_url_options = $this->parameters;
      $date_url_options['filter_date'] = date('m/d/y', strtotime($item->date));
      $date_url_options['filter_length'] = 'day';
      $date_url_options['class'] = 'any';
      $date_url_options['groupex_class'] = 'groupex_table_class';
      unset($date_url_options['instructor']);
      unset($date_url_options['view_mode']);

      $items[$item->id] = [
        '#theme' => isset($this->parameters['groupex_class']) ? $this->parameters['groupex_class'] : 'groupex_class',
        '#class' => [
          'id' => trim($item->id),
          'name' => trim($item->title),
          'group' => trim($item->category),
          'description' => $item->desc,
          'address_1' => $item->address_1,
          'address_2' => trim($item->location),
          'date' => $item->date,
          'studio' => $item->studio,
          'date_short' => date('F, d', strtotime($item->date)),
          'time' => $item->start,
          'duration' => sprintf('%d min', trim($item->length)),
          'instructor' => $item->instructor,
//          'class_id' => $item->class_id, //doesn't exist in API, needs to be added through processData().
          'class_link' => Url::fromRoute('openy_group_schedules.all_schedules_search_results', [], ['query' => $class_url_options]),
          'instructor_link' => Url::fromRoute('openy_group_schedules.all_schedules_search_results', [], ['query' => $instructor_url_options]),
          'date_link' => Url::fromRoute('openy_group_schedules.all_schedules_search_results', [], ['query' => $date_url_options]),
          'calendar' => $item->calendar,
        ],
      ];
    }

    // Pack classes into the schedule.
    $schedule = [];

    // There 3 types of schedules.
    // Day: show classes for single day.
    // Week: show classes for week grouped by day.
    // Location: show classes for 1 day grouped by location.
    $schedule['type'] = isset($this->parameters['filter_length']) ? $this->parameters['filter_length'] : 'day';
    if (!empty($this->parameters['location']) && is_array($this->parameters['location']) && count($this->parameters['location']) > 1) {
      $schedule['type'] = 'location';
    }
    if (!empty($this->parameters['class']) && is_numeric($this->parameters['class'])) {
      $schedule['type'] = 'week';
    }
    if (!empty($this->parameters['instructor'])) {
      $schedule['type'] = 'instructor';
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
          $location_id = $this->parameters['location'];
          $category = $this->parameters['category'] == 'any' ? NULL : $this->parameters['category'];
          $schedule['pdf_href'] = $this->groupexHelper->getPdfLink($location_id, $this->parameters['filter_timestamp'], $category);
        }

        // If no location selected show date instead of title.
        if (empty($this->parameters['location'])) {
          $schedule['title'] = $filter_date->format(GroupexRequestTrait::$dateFullFormat);
        }
        break;

      case 'week':
        $schedule['days'] = [];
        foreach ($items as $id => $class) {
          $schedule['days'][$this->enrichedData[$id]->day]['classes'][] = $class;
          $schedule['days'][$this->enrichedData[$id]->day]['day_short'] = DrupalDateTime::createFromFormat(GroupexRequestTrait::$dateFullFormat, $this->enrichedData[$id]->day, $this->timezone)->format('l, F j');
          $schedule['days'][$this->enrichedData[$id]->day]['date_link'] = $class['#class']['date_link'];
        }
        // Pass 'View This Week’s PDF' href if some location selected.
        if (!empty($this->parameters['location'])) {
          $location = $this->parameters['location'];
          $category = $this->parameters['category'] == 'any' ? NULL : $this->parameters['category'];
          $schedule['pdf_href'] = $this->groupexHelper->getPdfLink($location, $this->parameters['filter_timestamp'], $category);
        }

        // If no location selected show date instead of title.
        if (empty($this->parameters['location'])) {
          $schedule['day'] = $filter_date->format(GroupexRequestTrait::$dateFullFormat);
        }
        break;

      case 'location':
        $schedule['locations'] = [];
        $location_id = NULL;
        $locations_ids = \Drupal::entityQuery('mapping')
          ->condition('type', 'location')
          ->execute();
        $locations = \Drupal::entityTypeManager()
        ->getStorage('mapping')->loadMultiple($locations_ids);
        foreach ($items as $id => $class) {
          $short_location_name = trim($this->enrichedData[$id]->location);
          foreach ($locations as $location) {
            if ($location->get('name')->value == $short_location_name) {
              $field_groupex_id = $location->field_groupex_id->getValue();
              $location_id = isset($field_groupex_id[0]['value']) ? $field_groupex_id[0]['value'] : FALSE;
            }
          }
          $category = $this->parameters['category'] == 'any' ? NULL : $this->parameters['category'];
          $pdf_href = $this->groupexHelper->getPdfLink($location_id, $this->parameters['filter_timestamp'], $category);
          $schedule['locations'][$short_location_name]['classes'][] = $class;
          $schedule['locations'][$short_location_name]['pdf_href'] = $pdf_href;
        }
        $schedule['filter_date'] = date(GroupexRequestTrait::$dateFullFormat, $this->parameters['filter_timestamp']);
        break;

      case 'instructor':
        // Filter classes by instructor.
        $schedule['days'] = [];

        foreach ($items as $id => $class) {
          if ($class['#class']['instructor'] == $this->parameters['instructor']) {
            $class_date = DrupalDateTime::createFromFormat(
              GroupexRequestTrait::$dateFullFormat,
              $this->enrichedData[$id]->day,
              $this->timezone
            );

            $schedule['days'][$this->enrichedData[$id]->day]['classes'][] = $class;
            $schedule['days'][$this->enrichedData[$id]->day]['day_short'] = $class_date->format('l, F j');

            // Adjust query options to fit class date.
            $date_url_options['filter_date'] = $class_date->format(GroupexRequestTrait::$dateFilterFormat);
            $date_url_options['filter_timestamp'] = $class_date->format('U');

            $url = Url::fromRoute('openy_group_schedules.all_schedules_search_results', [], ['query' => $date_url_options]);
            $schedule['days'][$this->enrichedData[$id]->day]['date_link'] = $url;
          }
        }

        $schedule['instructor_location'] = t('Schedule for <span class="name"><span class="icon icon-user"></span>@name</span>', [
          '@name' => reset($schedule['days'])['classes'][0]['#class']['instructor'],
        ]);

        // Pass 'View This Week’s PDF' href if some location selected.
        if (!empty($this->parameters['location'])) {
          $location = $this->parameters['location'];
          $category = $this->parameters['category'] == 'any' ? NULL : $this->parameters['category'];
          $schedule['pdf_href'] = $this->groupexHelper->getPdfLink($location, $this->parameters['filter_timestamp'], $category);
        }

        // If no location selected show date instead of title.
        if (empty($this->parameters['location'])) {
          $schedule['day'] = $filter_date->format(GroupexRequestTrait::$dateFullFormat);
        }
        break;
    }

    $this->schedule = $schedule;
    return $this->schedule;
  }

  /**
   * Get form item options.
   *
   * @param array|null $data
   *   Data to iterate, or NULL.
   * @param string $key
   *   Key name.
   * @param string $value
   *   Value name.
   *
   * @return array
   *   Array of options.
   */
  public static function getOptions($data, $key, $value) {
    $options = [];
    if (empty($data) && !is_array($data)) {
      return [];
    }

    foreach ($data as $item) {
      $options[$item->$key] = $item->$value;
    }

    return $options;
  }

  /**
   * Fetch data from the server.
   */
  private function getData() {
    $this->rawData = [];

    $class = !empty($this->parameters['class']) ? $this->parameters['class'] : '';
    $category = !empty($this->parameters['category']) ? $this->parameters['category'] : '';

    // One of the 3 search parameters should be provided:
    // 1. Location.
    // 2. Class name.
    // 3. Category.
    if (
      !isset($this->parameters['location']) &&
      $class == 'any' &&
      $category == 'any') {
      return;
    }

    $options = [
      'query' => [
        'schedule' => TRUE,
        'desc' => 'true',
      ],
    ];

    // Location is optional.
    if (!empty($this->parameters['location'])) {
      $options['query']['location'] = array_filter([$this->parameters['location']]);
    }

    // Category is optional.
    if ($category !== 'any') {
      $options['query']['category'] = $category;
    }

    // Class is optional.
    if ($class !== 'any') {
      $options['query']['class'] = self::$idStrip . $class;
    }

    // Filter by date.
    $interval = 'P1D';
    if ($this->parameters['filter_length'] == 'week') {
      $interval = 'P1W';
    }
    $date = DrupalDateTime::createFromTimestamp($this->parameters['filter_timestamp'], $this->timezone);

    $options['query']['start'] = $date->getTimestamp();
    $options['query']['end'] = $date->add(new \DateInterval($interval))->modify('-1 day')->getTimestamp();

    $data = $this->request($options);

    // Classes IDs has some garbage withing the IDs.
    $class_name_options = $this->getOptions($this->request(['query' => ['classes' => TRUE]]), 'id', 'title');
    $dirty_keys = array_keys($class_name_options);
    $refined_keys = array_map(function ($item) {
      return str_replace(GroupexRequestTrait::$idStrip, '', $item);
    }, $dirty_keys);
    $refined_options = array_combine(array_values($class_name_options), $refined_keys);

    $raw_data = [];

    if (!empty($data) && is_array($data)) {
      foreach ($data as $item) {
        $raw_data[$item->id] = $item;
        if (isset($refined_options[$item->title])) {
          $raw_data[$item->id]->class_id = $refined_options[$item->title];
        }
      }
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

      // Get start and end time. Add To Calendar does not support All Day events.
      if ($item->time == "All Day") {
        $item->start = "12:00am";
        $item->end = "11:59pm";
      } else {
        preg_match("/(.*)-(.*)/i", $item->time, $output);
        $item->start = $output[1];
        $item->end = $output[2];
      }

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

      // Add calendar data.
      $date_start = DrupalDateTime::createFromFormat('l, F j, Y g:ia', $item->date . ' ' . $item->start);
      $date_end = DrupalDateTime::createFromFormat('l, F j, Y g:ia', $item->date . ' ' . $item->end);
      $date_start = $date_start->format('Y-m-d H:i:s');
      $date_end = $date_end->format('Y-m-d H:i:s');
      $item->calendar = [
        'atc_date_start' => $date_start,
        'atc_date_end' => $date_end,
        'atc_timezone' => date_default_timezone_get(),
        'atc_title' => $item->title,
        'atc_description' => 'Visit ' . $item->category . ' with ' . strip_tags($item->instructor) . PHP_EOL . 'Class will take place at ' . $item->studio . '.',
        'atc_location' => $item->location,
        'atc_organizer' => $item->instructor,
      ];
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
      $filtered = array_filter($filtered, function ($item) use ($param) {
        if (in_array($item->time_of_day, $param['time_of_day'])) {
          return TRUE;
        }
        return FALSE;
      });
    }

    // GroupEx Pro response have some redundant data. Filter it out.
    if ($param['filter_length'] == 'day') {
      // Filter out by the date. Cut off days before.
      $filtered = array_filter($filtered, function ($item) use ($param) {
        if ($item->timestamp >= $param['filter_timestamp']) {
          return TRUE;
        }
        return FALSE;
      });

      // Filter out by the date. Cut off days after.
      $filtered = array_filter($filtered, function ($item) use ($param) {
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

    // Replace <span class="subbed"> with normal text.
    foreach ($data as &$item) {
      preg_match('/<span class=\"subbed\".*><br>(.*)<\/span>/', $item->address_1, $test);
      if (!empty($test)) {
        $item->address_1 = str_replace($test[0], ' ' . $test[1], $item->address_1);
      }
      preg_match('/<span class=\"subbed\".*><br>(.*)<\/span>/', $item->instructor, $test1);
      if (!empty($test1)) {
        $test1[1] = str_replace('(sub for ', '', $test1[1]);
        $test1[1] = str_replace(')', '', $test1[1]);
        $item->instructor = str_replace($test1[0], '<br><span class="fa fa-refresh" aria-hidden="true"></span><span class="sub">' . $test1[1] . '</span>', $item->instructor);
      }
    }

    $this->processedData = $data;
  }

  /**
   * Normalize parameters.
   *
   * @param array $parameters
   *   Input parameters.
   *
   * @return array
   *   Normalized parameters.
   */
  public static function normalizeParameters(array $parameters) {
    $normalized = $parameters;

    $request_time = \Drupal::time()->getRequestTime();
    $timezone = new \DateTimeZone(\Drupal::config('system.date')->get('timezone')['default']);

    // The old site has a habit to provide empty filter_date. Fix it here.
    if (empty($normalized['filter_date'])) {
      $date = DrupalDateTime::createFromTimestamp($request_time, $timezone);
      $normalized['filter_date'] = $date->format(self::$dateFilterFormat);
    }

    // Convert date parameter to timestamp.
    // Date parameter can by with leading zero or not.
    $origin_dtz = new \DateTimeZone(date_default_timezone_get());
    $remote_dtz = new \DateTimeZone(\Drupal::config('system.date')->get('timezone')['default']);
    $origin_dt = new \DateTime('now', $origin_dtz);
    $remote_dt = new \DateTime('now', $remote_dtz);
    $offset = $origin_dtz->getOffset($origin_dt) - $remote_dtz->getOffset($remote_dt);

    // Add offset. Function strtotime() uses default timezone.
    if ($timestamp = strtotime($normalized['filter_date'])) {
      $timestamp += $offset;
    }
    else {
      $date = DrupalDateTime::createFromTimestamp($request_time, $timezone);
      $timestamp = $date->format('U');
    }

    // Add timestamp.
    $normalized['filter_timestamp'] = $timestamp;

    // Finally, normalize filter_date.
    $date = DrupalDateTime::createFromTimestamp($normalized['filter_timestamp'], $timezone);
    $normalized['filter_date'] = $date->format(self::$dateFilterFormat);

    // Add default filter_length.
    if (!isset($normalized['filter_length'])) {
      $normalized['filter_length'] = 'day';
    }

    // Apply 'week' logic if class is selected.
    if (isset($parameters['class']) && is_numeric($parameters['class'])) {
      $normalized['filter_length'] = 'week';
      $normalized['view_mode'] = 'class';
      $normalized['groupex_class'] = 'groupex_table_class_individual';
    }

    return $normalized;
  }

  /**
   * Check if results are empty.
   *
   * @return bool
   *   True if schedule is empty, false otherwise.
   */
  public function isEmpty() {
    return empty($this->processedData);
  }

  /**
   * Get PDF link to location schedule.
   *
   * @param int $location
   *   Location ID.
   * @param int|bool $timestamp
   *   Timestamp.
   * @param int|bool $category
   *   Category.
   *
   * @return \Drupal\Core\Url
   *   Link.
   */
  public function getPdfLink($location, $timestamp = FALSE, $category = FALSE) {
    $query = [
      'font' => 'larger',
      'account' => GroupexRequestTrait::$account,
      'l' => $location,
    ];

    if ($timestamp) {
      $query['week'] = strtotime('Monday this week', $timestamp);
    }

    if ($category) {
      $query['c'] = $category;
    }

    return Url::fromUri(self::PRINT_URI, ['query' => $query]);
  }

}
