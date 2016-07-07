<?php

namespace Drupal\ymca_retention;

/**
 * Storage for ymca retention campaign.
 */
class AnonymousCookieStorage {

  /**
   * Get cookie by key.
   *
   * @param string $key
   *   Cookie key.
   *
   * @return null|string
   *   Cookie value or NULL.
   */
  public static function get($key) {
    $name = 'Drupal_visitor_' . $key;
    if (!empty($_COOKIE[$name])) {
      return $_COOKIE[$name];
    }
    return NULL;
  }

  /**
   * Set cookie value by key.
   *
   * @param string $key
   *   Cookie key.
   * @param string $value
   *   Cookie value.
   */
  public static function set($key, $value) {
    // Set cookie for 1 day.
    setrawcookie('Drupal.visitor.' . $key, rawurlencode($value), REQUEST_TIME + 86400, '/');
  }

  /**
   * Delete cookie.
   *
   * @param string $key
   *   Cookie key.
   */
  public static function delete($key) {
    user_cookie_delete($key);
  }

}
