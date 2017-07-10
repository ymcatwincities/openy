<?php

namespace Drupal\webform\Utility;

/**
 * Provides HTML helper functions.
 */
class WebformHtmlHelper {

  /**
   * Determine if a string value contains HTML markup or entities.
   *
   * @param string $string
   *   A string.
   *
   * @return bool
   *   TRUE if the string value contains HTML markup or entities.
   */
  public static function containsHtml($string) {
    return (preg_match('/(<[a-z][^>]*>|&(?:[a-z]+|#\d+);)/i', $string)) ? TRUE : FALSE;
  }

}
