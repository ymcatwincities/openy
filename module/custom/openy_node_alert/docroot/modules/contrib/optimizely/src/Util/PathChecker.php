<?php

namespace Drupal\optimizely\Util;

use Drupal\optimizely\Util\LookupPath;

/**
 * Provides static methods to check path validity, etc.
 */
class PathChecker {

use LookupPath;

  /**
   * Validate the target paths.
   *
   * @param array $project_paths
   *   An array of the paths to validate.
   *
   * @return bool|string
   *   Boolean of TRUE if the paths are valid or
   *   a string of the path that failed.
   */
  public static function validatePaths(array $project_paths) {

    // Validate entered paths to confirm the paths exist on the website.
    foreach ($project_paths as $path) {

      // Check for sitewide wildcard.
      if (strpos($path, '*') === 0) {

        // Must be just the wildcard itself with nothing trailing.
        if ($path != '*') {
          return $path;
        }

        return (count($project_paths) == 1) ? TRUE : $path;

      }
      // Check for path wildcards.
      elseif (strpos($path, '*') !== FALSE) {

        $project_wildpath = substr($path, 0, -2);
        if (\Drupal::pathValidator()->isValid($project_wildpath) == FALSE) {

          // Look for entries in url_alias.
          $query = \Drupal::database()->query("SELECT * FROM {url_alias} WHERE
            source LIKE :project_wildpath OR alias LIKE :project_wildpath",
            [':project_wildpath' => $project_wildpath . '%']);
          $results = $query->fetchCol(0);
          $project_wildpath_match = count($results);

          // No matches found for wildcard path.
          if (!$project_wildpath_match) {
            return $path;
          }

        }

      }
      // Check for parameters.
      elseif (strpos($path, '?') !== FALSE) {

        // Look for entries in menu_router.
        $project_parmpath = substr($path, 0, strpos($path, '?'));

        // Look for entry in url_alias table.
        if (self::lookupPathAlias($path) === FALSE &&
            self::lookupSystemPath($path) === FALSE &&
            \Drupal::pathValidator()->isValid($project_parmpath) == FALSE) {
          return $path;
        }

      }
      // Validation if path valid menu router entry,
      // includes support for <front>.
      elseif (\Drupal::pathValidator()->isValid($path) == FALSE) {

        // Look for entry in url_alias table.
        if (self::lookupPathAlias($path) === FALSE &&
            self::lookupSystemPath($path) === FALSE) {
          return $path;
        }

      }

    }

    return TRUE;

  }

  /**
   * Compare target path against the project paths to confirm they're unique.
   *
   * @param array $target_paths
   *   The paths entered for a new project entry, OR
   *   the paths of an existing project entry that has been enabled.
   * @param int|null $target_oid
   *   The oid of the project entry that has been enabled, or NULL.
   *
   * @return bool|
   *   $target_path: the path that is a duplicate that must be addressed to
   *   enable or create the new project entry, or TRUE if unique paths.
   */
  public static function uniquePaths(array $target_paths, $target_oid = NULL) {

    // Look up alternative paths.
    $target_paths = self::collectAlias($target_paths);

    // Look for duplicate paths in submitted $target_paths.
    $duplicate_target_path = self::duplicateCheck($target_paths);

    // Look for duplicate paths within target paths.
    if (!$duplicate_target_path) {

      // Collect all of the existing project paths that are enabled.
      $query = \Drupal::database()->select('optimizely', 'o', ['target' => 'slave'])
        ->fields('o', ['oid', 'project_title', 'path'])
        ->condition('o.enabled', 1, '=');

      // Add target_oid to query when it's an update, $target_oid is defined.
      if ($target_oid != NULL) {
        $query = $query->condition('o.oid', $target_oid, '<>');
      }

      $projects = $query->execute();

      // No other enabled projects.
      if ($query->countQuery()->execute()->fetchField() == 0) {
        return [TRUE, NULL];
      }

      $all_project_paths = [];

      // Build array of all the project entry paths.
      foreach ($projects as $project) {

        // Collect all of the path values and merge into collective array.
        $project_paths = unserialize($project->path);
        $all_project_paths = array_merge($all_project_paths, $project_paths);

      }

      // Add any additional aliases to catch all match possiblities.
      $all_project_paths = self::collectAlias($all_project_paths);

      // Convert array into string for drupal_match_path().
      $all_project_paths_string = implode("\n", $all_project_paths);

      // Check all of the paths for all of the active project entries
      // to make sure the paths are unique.
      foreach ($target_paths as $target_path) {

        // "*" found in path.
        if (strpos($target_path, '*') !== FALSE) {

          // Look for wild card match if not sitewide.
          if (strpos($target_path, '*') !== 0) {

            $target_path = substr($target_path, 0, -2);

            // Look for duplicate path due to wild card.
            foreach ($all_project_paths as $all_project_path) {

              if (strpos($all_project_path, $target_path) === 0 && $all_project_path != $target_path) {
                return [$project->project_title, $target_path];
              }

            }

          }
          // If sitewide wild card then it must be the only enabled path
          // to be unique.
          elseif (strpos($target_path, '*') === 0 &&
                  (count($target_paths) > 1 || count($all_project_paths) > 0)) {
            return [$project->project_title, $target_path];
          }

          // Look for sitewide wild card in target project paths.
          if (in_array('*', $all_project_paths)) {
            return [$project->project_title, $target_path];
          }

        }
        // Parameters found, collect base path for comparison
        // to the other project path entries.
        elseif (strpos($target_path, '?') !== FALSE) {
          $target_path = substr($target_path, 0, strpos($target_path, '?'));
        }

        // Look for duplicates.
        if (\Drupal::service('path.matcher')->matchPath($target_path, $all_project_paths_string)) {
          return [$project->project_title, $target_path];
        }

      }

      return [TRUE, NULL];

    }
    else {
      return [NULL, $duplicate_target_path];
    }

  }

  /**
   * Lookup all alternatives to the group of paths - alias, <front>.
   *
   * @param array $paths
   *   A set of paths to be reviewed for alternatives.
   *
   * @return array
   *   An updated list of paths that include the additional source
   *   and alias values.
   */
  private static function collectAlias(array $paths) {

    // Add alternative values - alias, source, <front> to ensure matches
    // also check different possibilities.
    foreach ($paths as $path_count => $path) {

      // Remove parameters.
      if (strpos($path, '?') !== FALSE) {
        $path = substr($path, 0, strpos($path, '?'));
        $paths[$path_count] = $path;
      }

      if (self::lookupPathAlias($path)) {
        $paths[] = self::lookupPathAlias($path);
      }
      if (self::lookupSystemPath($path)) {
        $paths[] = self::lookupSystemPath($path);
      }

      // Collect all the possible values to match <front>.
      if ($path == '<front>') {

        $frontpage = \Drupal::config('system.site')->get('page.front');
        if ($frontpage) {
          $paths[] = $frontpage;
          $paths[] = self::lookupPathAlias($frontpage);
        }

      }

    }

    return $paths;

  }

  /**
   * Compare paths to ensure each item resolves to a unique entry.
   *
   * @param array $paths
   *   A set of paths to be reviewed for uniqueness.
   *
   * @return bool|array
   *   FALSE if no duplicates found, otherwise the duplicate path is returned.
   */
  private static function duplicateCheck(array $paths) {

    $unreviewed_paths = $paths;

    // Check all of the paths.
    foreach ($paths as $path) {

      // Remove path that's being processed from the front of the list.
      array_shift($unreviewed_paths);

      // "*" found in path.
      if (strpos($path, '*') !== FALSE) {

        // Look for wild card match that's not sitewide (position not zero (0))
        if (strpos($path, '*') !== 0) {

          $path = substr($path, 0, -2);

          foreach ($unreviewed_paths as $unreviewed_path) {
            if (strpos($unreviewed_path, $path) !== FALSE) {
              return $path . '/*';
            }
          }

        }
        // If sitewide wild card then it must be the only path in path set.
        elseif (strpos($path, '*') === 0 && count($paths) > 1) {
          return $path;
        }

      }
      elseif (in_array($path, $unreviewed_paths)) {
        return $path;
      }

    }

    return FALSE;

  }

}
