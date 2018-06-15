<?php

namespace Drupal\openy_stats;

/**
 * Class NodeStats.
 *
 * @package Drupal\openy_stats
 */
class NodeStats {

  /**
   * Get module list.
   *
   * @return array
   *   Module list.
   */
  public function getNodeStats() {
    $db = \Drupal::database();
    $result = $db->query('SELECT type, status, count(*) as count FROM {node_field_data} GROUP BY type, status')->fetchAll(\PDO::FETCH_ASSOC);

    foreach ($result as $id => $data) {
      if (!isset($stats[$data['type']])) {
        $stats[$data['type']] = [];
      }
      if ($data['status'] == '0') {
        $stats[$data['type']]['unpublished'] = (int)$data['count'];

      }
      elseif ($data['status'] == '1') {
        $stats[$data['type']]['published'] = (int)$data['count'];
      }
    }

    foreach ($stats as &$module) {
      if (!isset ($module['unpublished'])) {
        $module['unpublished'] = 0;
      }
      if (!isset ($module['published'])) {
        $module['published'] = 0;
      }
      $module['total'] = $module['unpublished'] + $module['published'];
    }

    return $stats;
  }

}
