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
  public function dashboard( Request $request, string $location ) {
    $checked_locations = [];
    if (!empty($location)) {
      $checked_locations = explode(',', $location);
    }
    return [
      '#theme' => 'openy_repeat_schedule_dashboard',
      '#locations' => $this->getLocations(),
      '#checked_locations' => $checked_locations,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function ajaxScheduler( Request $request, string $location, string $date ) {
    if (empty($date)) {
      $date = date('d-m-Y 00:00:00');
    }
    $date = strtotime($date);

    $year = date('Y', $date);
    $month = date('m', $date);
    $day = date('d', $date);
    $week = date('W', $date);
    $weekday = date('N', $date);

    $sql = "SELECT DISTINCT n.nid, re.id, nd.title as location, nds.title as name, DATE_FORMAT(FROM_UNIXTIME(re.start), '%h:%i%p') as time
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
   * {@inheritdoc}
   */
  public function locations() {
    return [
      '#theme' => 'openy_repeat_schedule_locations',
      '#locations' => $this->getLocations(),
    ];
  }

}
