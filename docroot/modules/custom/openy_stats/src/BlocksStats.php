<?php

namespace Drupal\openy_stats;

/**
 * Class BlocksStats
 *
 * @package Drupal\openy_stats
 */
class BlocksStats {

  /**
   * Get blocks status.
   *
   * @return array
   *   Blocks stats.
   */
  public function getBlocksStats() {
    $db = \Drupal::database();
    return $db->query('SELECT type, count(*) as count FROM {block_content_field_data} GROUP BY type')->fetchAll(\PDO::FETCH_ASSOC);
  }

}
