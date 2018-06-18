<?php

namespace Drupal\openy_stats;

/**
 * Class ParagraphStats
 *
 * @package Drupal\openy_stats
 */
class ParagraphStats {

  /**
   * Get paragraphs status.
   *
   * @return array
   *   Paragraphs stats.
   */
  public function getPrgfStats() {
    $db = \Drupal::database();
    return $db->query('SELECT type, count(*) as count FROM {paragraphs_item} GROUP BY type')->fetchAll(\PDO::FETCH_ASSOC);
  }

}
