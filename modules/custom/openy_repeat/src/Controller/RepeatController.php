<?php

namespace Drupal\openy_repeat\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\node\Entity\Node;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * {@inheritdoc}
 */
class RepeatController extends ControllerBase {

  /**
   * {@inheritdoc}
   */
  public function dashboard( Request $request, $location, $category = NULL) {
    $checked_categories = [];
    if (!empty($category)) {
      $checked_categories = explode(',', $category);
    }
    $checked_locations = [];
    if (!empty($location)) {
      $checked_locations = explode(',', $location);
    }
    return [
      '#theme' => 'openy_repeat_schedule_dashboard',
      '#locations' => $this->getLocations(),
      '#categories' => $this->getCategories(),
      '#checked_locations' => $checked_locations,
      '#checked_categories' => $checked_categories,
    ];
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

    $sql = "SELECT DISTINCT
              n.nid,
              re.id,
              nd.title as location,
              nds.title as name,
              re.class,
              CAST(re.duration / 60 AS CHAR(1)) as duration_hours,
              CAST(re.duration % 60 AS CHAR(2)) as duration_minutes,
              re.room,
              re.instructor as instructor,
              re.category,
              TRIM(LEADING '0' FROM (DATE_FORMAT(FROM_UNIXTIME(re.start), '%h:%i%p'))) as time
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
                (re.start <= UNIX_TIMESTAMP(NOW()))
                AND
                (re.end >= UNIX_TIMESTAMP(NOW()))
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

    $sql .= " ORDER BY re.start";

    $values[':year'] = $year;
    $values[':month'] = $month;
    $values[':day'] = $day;
    $values[':week'] = $week;
    $values[':weekday'] = $weekday;

    $connection = \Drupal::database();
    $query = $connection->query($sql, $values);
    $result = $query->fetchAll();

    $locations_info = $this->getLocationsInfo();
    foreach ($result as $key => $item) {
      $result[$key]->location_info = $locations_info[$item->location];
    }

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

    $connection = \Drupal::database();
    $query = $connection->query($sql);

    return $query->fetchCol();
  }

  /**
   * Get detailed info about Location (aka branch).
   */
  public function getLocationsInfo() {
    $nids = \Drupal::entityQuery('node')
      ->condition('type','branch')
      ->execute();
    $branches = Node::loadMultiple($nids);

    $data = [];
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


  /**
   * Return Categories from chain "Session" -> "Class" -> "Activity" -> "Program sub-category".
   *
   * @return array
   */
  public function getCategories() {
    $sql = "SELECT title 
            FROM {node_field_data} n
            WHERE n.type = 'program_subcategory'
            AND n.status = '1'";

    $connection = \Drupal::database();
    $query = $connection->query($sql);
    return $query->fetchCol();
  }

  /**
   * {@inheritdoc}
   */
  public function locations() {
    return [
      '#theme' => 'openy_repeat_schedule_locations',
      '#locations' => $this->getLocations(),
    ];
  }

}
