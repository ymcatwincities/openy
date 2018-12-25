<?php

namespace Drupal\ymca_aliases;

use Drupal\Component\Utility\Unicode;

/**
 * Cleans a string to use in URL.
 *
 * @package Drupal\ymca_aliases.
 */
class UrlCleaner {

  /**
   * Clean a string.
   *
   * @param string $str
   *   String to clean.
   *
   * @return string
   *   Cleaned string.
   */
  static public function clean($str) {
    $clean = preg_replace('/[^a-zA-Z0-9\/_|+ -]/', '', $str);
    $clean = Unicode::strtolower(trim($clean, '_'));
    $clean = preg_replace('/[\/_|+ -]+/', '_', $clean);
    return $clean;
  }

}
