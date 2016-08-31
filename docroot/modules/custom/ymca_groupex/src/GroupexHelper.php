<?php

namespace Drupal\ymca_groupex;

use Drupal\Core\Url;

/**
 * Groupex helper.
 *
 * @package Drupal\ymca_groupex.
 */
class GroupexHelper {

  use GroupexRequestTrait;

  /**
   * PDF print uri.
   */
  const PRINT_URI = 'http://www.groupexpro.com/ymcatwincities/print.php';

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
