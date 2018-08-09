<?php

namespace Drupal\openy_repeat\Controller;

use Drupal\Core\Controller\ControllerBase;
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
    $sql = "SELECT
              n.nid,
              nd.title,
              em.field_location_email_value AS email,
              ph.field_location_phone_value AS phone,
              CONCAT_WS(' ', ad.field_location_address_locality, ad.field_location_address_address_line1, ad.field_location_address_postal_code, ad.field_location_address_administrative_area, ad.field_location_address_country_code) AS adress,
              bh.field_branch_hours_hours_mon AS Mon,
              bh.field_branch_hours_hours_tue AS Tue,
              bh.field_branch_hours_hours_wed AS Wed,
              bh.field_branch_hours_hours_thu AS Thu,
              bh.field_branch_hours_hours_fri AS Fri,
              bh.field_branch_hours_hours_sat AS Sat,
              bh.field_branch_hours_hours_sun AS Sun
            FROM {node} n
            INNER JOIN node__field_location_email em ON n.nid = em.entity_id AND em.bundle = 'branch'
            INNER JOIN node__field_location_phone ph ON n.nid = ph.entity_id AND ph.bundle = 'branch'
            LEFT JOIN node__field_location_address ad ON n.nid = ad.entity_id AND ad.bundle = 'branch'
            INNER JOIN node__field_branch_hours bh ON n.nid = bh.entity_id AND bh.bundle = 'branch'
            INNER JOIN node_field_data nd ON n.nid = nd.nid
            WHERE n.type = 'branch'";

    $connection = \Drupal::database();
    $query = $connection->query($sql);
    $select_data = $query->fetchAll();

    $data = [];
    foreach ($select_data as $item) {
      $days = [
        'Mon' => $item->Mon,
        'Tue' => $item->Tue,
        'Wed' => $item->Wed,
        'Thu' => $item->Thu,
        'Fri' => $item->Fri,
        'Sat' => $item->Sat,
        'Sun' => $item->Sun
      ];
      $item->days = $this->getFormattedHours($days);
      $data[$item->title] = $item;
    }

    return $data;
  }

  public function getFormattedHours($data) {
    $lazy_hours = $groups = $rows = [];
    foreach ($data as $day => $value) {
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
