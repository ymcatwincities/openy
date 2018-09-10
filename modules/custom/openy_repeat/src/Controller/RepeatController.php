<?php

namespace Drupal\openy_repeat\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\node\Entity\Node;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\Core\Cache\CacheBackendInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Entity\Query\QueryFactory;

/**
 * {@inheritdoc}
 */
class RepeatController extends ControllerBase {

  /**
   * Cache default.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;

  /**
   * The Database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The entity query factory.
   *
   * @var \Drupal\Core\Entity\Query\QueryFactory
   */
  protected $entityQuery;

  /**
   * The EntityTypeManager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entity_type_manager;

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * Creates a new RepeatController.
   *
   * @param CacheBackendInterface $cache
   *   Cache default.
   * @param Connection $database
   *   The Database connection.
   * @param EntityTypeManager $entity_type_manager
   *   The EntityTypeManager.
   * @param DateFormatterInterface $date_formatter
   *   The Date formatter.
   */
  public function __construct(CacheBackendInterface $cache, Connection $database, QueryFactory $entity_query, EntityTypeManager $entity_type_manager, DateFormatterInterface $date_formatter) {
    $this->cache = $cache;
    $this->database = $database;
    $this->entityQuery = $entity_query;
    $this->entityTypeManager = $entity_type_manager;
    $this->dateFormatter = $date_formatter;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('cache.default'),
      $container->get('database'),
      $container->get('entity.query'),
      $container->get('entity_type.manager'),
      $container->get('date.formatter')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function ajaxScheduler( Request $request, $location, $date, $category) {
    if (empty($date)) {
      $date = date('F j, l 00:00:00');
    }
    $date = strtotime($date);

    $year = date('Y', $date);
    $month = date('m', $date);
    $day = date('d', $date);
    $week = date('W', $date);
    $weekday = date('N', $date);

    $timestamp_start = $date;
    // Next day.
    $timestamp_end = $date + 24 * 60 * 60;

    $sql = "SELECT DISTINCT
              n.nid,
              re.id,
              nd.title as location,
              nds.title as name,
              re.class,
              re.session,
              re.duration as duration,
              re.room,
              re.instructor as instructor,
              re.category,
              re.register_url as register_url,
              re.register_text as register_text,
              re.start as start_timestamp,
              re.end as end_timestamp,
              re.duration as duration
            FROM {node} n
            RIGHT JOIN {repeat_event} re ON re.session = n.nid
            INNER JOIN node_field_data nd ON re.location = nd.nid
            INNER JOIN node_field_data nds ON n.nid = nds.nid
            WHERE
              n.type = 'session'
              AND
              (
                (re.year = :year OR re.year = '*')
                AND
                (re.month = :month OR re.month = '*')
                AND
                (re.day = :day OR re.day = '*')
                AND
                (re.week = :week OR re.week = '*')
                AND
                (re.weekday = :weekday OR re.weekday = '*')
                AND
                (re.start <= :timestamp_end)
                AND
                (re.end >= :timestamp_start)
              )";

    $values = [];
    if (!empty($category)) {
      $sql .= "AND re.category IN ( :categories[] )";
      $values[':categories[]'] = explode(',', $category);
    }
    if (!empty($location)) {
      $sql .= "AND nd.title IN ( :locations[] )";
      $values[':locations[]'] = explode(',', $location);
    }
    $exclusions = $request->get('excl');
    if (!empty($exclusions)) {
      $sql .= "AND re.category NOT IN ( :exclusions[] )";
      $values[':exclusions[]'] = explode(',', $exclusions);
    }
    $limit = $request->get('limit');
    if (!empty($limit)) {
      $sql .= "AND re.category IN ( :limit[] )";
      $values[':limit[]'] = explode(',', $limit);
    }

    $values[':year'] = $year;
    $values[':month'] = $month;
    $values[':day'] = $day;
    $values[':week'] = $week;
    $values[':weekday'] = $weekday;
    $values[':timestamp_start'] = $timestamp_start;
    $values[':timestamp_end'] = $timestamp_end;

    $query = $this->database->query($sql, $values);
    $result = $query->fetchAll();

    $locations_info = $this->getLocationsInfo();

    $classesIds = [];
    foreach ($result as $key => $item) {
      $classesIds[$item->class] = $item->class;
    }
    $classes_info = $this->getClassesInfo($classesIds);

    foreach ($result as $key => $item) {
      $result[$key]->location_info = $locations_info[$item->location];
      $result[$key]->class_info = $classes_info[$item->class];

      $result[$key]->time_start_sort = $this->dateFormatter->format((int)$item->start_timestamp, 'custom', 'Hi');

      // Convert timezones for start_time and end_time.
      $result[$key]->time_start = $this->dateFormatter->format((int)$item->start_timestamp, 'custom', 'g:i');
      $result[$key]->time_end = $this->dateFormatter->format((int)$item->start_timestamp + $item->duration * 60, 'custom', 'g:iA');

      // Example of calendar format 2018-08-21 14:15:00.
      $result[$key]->time_start_calendar = $this->dateFormatter->format((int)$item->start_timestamp, 'custom', 'Y-m-d H:i:s');
      $result[$key]->time_end_calendar = $this->dateFormatter->format((int)$item->start_timestamp + $item->duration * 60, 'custom', 'Y-m-d H:i:s');

      // Durations.
      $result[$key]->duration_minutes = $item->duration % 60;
      $result[$key]->duration_hours = ($item->duration - $result[$key]->duration_minutes) / 60;
    }

    usort($result, function($item1, $item2){
      if ((int) $item1->time_start_sort == (int) $item2->time_start_sort) {
        return 0;
      }
      return (int) $item1->time_start_sort < (int) $item2->time_start_sort ? -1 : 1;
    });

    return new JsonResponse($result);
  }

  /**
   * Return Location from "Session" node type.
   *
   * @return array
   */
  public function getLocations() {
    $sql = "SELECT DISTINCT nd.title as location
            FROM {node} n
            INNER JOIN node__field_session_location l ON n.nid = l.entity_id AND l.bundle = 'session'
            INNER JOIN node_field_data nd ON l.field_session_location_target_id = nd.nid
            WHERE n.type = 'session'";

    $query = $this->database->query($sql);

    return $query->fetchCol();
  }

  /**
   * Get detailed info about Location (aka branch).
   */
  public function getLocationsInfo() {
    $data = [];
    $tags = ['node_list'];
    $cid = 'openy_repeat:locations_info';
    if ($cache = $this->cache->get($cid)) {
      $data = $cache->data;
    }
    else {
      $nids = $this->entityQuery
        ->get('node')
        ->condition('type','branch')
        ->execute();
      $nids_chunked = array_chunk($nids, 20, TRUE);
      foreach ($nids_chunked as $chunk) {
        $branches = $this->entityTypeManager->getStorage('node')->loadMultiple($chunk);
        if (!empty($branches)) {
          foreach ($branches as $node) {
            $days = $node->get('field_branch_hours')->getValue();
            $address = $node->get('field_location_address')->getValue();
            if (!empty($address[0])) {
              $address = array_filter($address[0]);
              $address = implode(', ', $address);
            }
            $data[$node->title->value] = [
              'nid' => $node->nid->value,
              'title' => $node->title->value,
              'email' => $node->field_location_email->value,
              'phone' => $node->field_location_phone->value,
              'address' => $address,
              'days' => !empty($days[0]) ? $this->getFormattedHours($days[0]) : [],
            ];
            $tags[] = 'node:' . $node->nid->value;
          }
        }
      }
      $this->cache->set($cid, $data, CacheBackendInterface::CACHE_PERMANENT, $tags);
    }

    return $data;
  }

  /**
   * Get detailed info about Class.
   */
  public function getClassesInfo($nids) {
    $data = [];
    $tags = [];
    $cid = 'openy_repeat:classes_info' . md5(json_encode($nids));
    if ($cache = $this->cache->get($cid)) {
      $data = $cache->data;
    }
    else {
      $nids_chunked = array_chunk($nids, 20, TRUE);
      foreach ($nids_chunked as $chunk) {
        $classes = $this->entityTypeManager->getStorage('node')->loadMultiple($chunk);
        if (!empty($classes)) {
          foreach ($classes as $node) {
            $data[$node->nid->value] = [
              'nid' => $node->nid->value,
              'title' => $node->title->value,
              'description' => html_entity_decode(strip_tags(text_summary($node->field_class_description->value, $node->field_class_description->format, 600))),
            ];
            $tags[] = 'node:' . $node->nid->value;
          }
        }
      }
      $this->cache->set($cid, $data, CacheBackendInterface::CACHE_PERMANENT, $tags);
    }

    return $data;
  }


  public function getFormattedHours($data) {
    $lazy_hours = $groups = $rows = [];
    foreach ($data as $day => $value) {
      // Do not process label. Store it name for later usage.
      if ($day == 'hours_label') {
        continue;
      }

      $day = str_replace('hours_', '', $day);
      $value = $value ? $value : 'closed';
      $lazy_hours[$day] = $value;
      if ($groups && end($groups)['value'] == $value) {
        $array_keys = array_keys($groups);
        $group = &$groups[end($array_keys)];
        $group['days'][] = $day;
      }
      else {
        $groups[] = [
          'value' => $value,
          'days' => [$day],
        ];
      }
    }

    foreach ($groups as $group_item) {
      $title = sprintf('%s - %s', ucfirst(reset($group_item['days'])), ucfirst(end($group_item['days'])));
      if (count($group_item['days']) == 1) {
        $title = ucfirst(reset($group_item['days']));
      }
      $hours = $group_item['value'];
      $rows[] = [$title . ':', $hours];
    }

    return $rows;
  }

}
