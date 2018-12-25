<?php

namespace Drupal\optimizely\Util;

/**
 * Implements methods for looking up path aliases and system paths.
 */
trait LookupPath {

  /**
   * Helper function to lookup a path alias, given a path.
   *
   * This function acts as an adapter and passes back a return value
   * like those of drupal_lookup_path(), which has been removed
   * as of Drupal 8.
   */
  public static function lookupPathAlias($path) {

    $path = LookupPath::checkPath($path);
    $alias = \Drupal::service('path.alias_manager')->getAliasByPath($path);
    return (strcmp($alias, $path) == 0) ? FALSE : $alias;
  }

  /**
   * Helper function to lookup a system path, given a path alias.
   *
   * This function acts as an adapter and passes back a return value
   * like those of drupal_lookup_path(), which has been removed
   * as of Drupal 8.
   */
  public static function lookupSystemPath($alias) {

    $alias = LookupPath::checkPath($alias);
    $path = \Drupal::service('path.alias_manager')->getPathByAlias($alias);
    return (strcmp($path, $alias) == 0) ? FALSE : $path;
  }

  /**
   * Ensure that $path starts with a forward slash.
   *
   * The alias_manager requires it.
   */
  public static function checkPath($path) {
    return ($path[0] == '/') ? $path : '/' . $path;
  }

}
