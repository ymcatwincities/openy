<?php
/**
 * @file
 * Helper trait.
 */

namespace Drupal\ymca_activity_finder;

/**
 * Class ActivityFinderTrait.
 *
 * @package Drupal\ymca_activity_finder.
 */
trait ActivityFinderTrait {

  /**
   * Clean title.
   *
   * @param string $str
   *   Title to clean.
   *
   * @return string
   *   Cleaned title.
   */
  static public function cleanTitle($str) {
    $clean = preg_replace('/[^a-zA-Z0-9\/_|+ -]/', '', $str);
    $clean = strtolower(trim($clean, '_'));
    $clean = preg_replace('/[\/_|+ -]+/', '_', $clean);
    return $clean;
  }

}
