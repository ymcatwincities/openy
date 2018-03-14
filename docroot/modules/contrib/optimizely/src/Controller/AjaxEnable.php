<?php

namespace Drupal\optimizely\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;

use Drupal\optimizely\Util\PathChecker;
use Drupal\optimizely\Util\CacheRefresher;

/**
 * Respond to ajax requests when Enable/Disable checkbox is clicked.
 *
 * These checkboxes are on the project listing form.
 */
class AjaxEnable {

  /**
   * Enable or disable the project.
   */
  public static function enableDisable() {

    // Default.
    $unique_path = FALSE;
    $default_project = FALSE;
    $message = '';

    // Retrieve the json POST values.
    $target_oid = $_POST['target_oid'];
    $target_enable = $_POST['target_enable'];

    // Lookup the current project settings.
    $query = \Drupal::database()->select('optimizely', 'o', ['target' => 'slave'])
      ->fields('o', ['path', 'project_code'])
      ->condition('o.oid', $target_oid, '=');
    $result = $query->execute()->fetchObject();

    $target_paths = unserialize($result->path);

    // Only check path values if project is being enabled,
    // Project is currently disabled (FALSE) and is now being enabled (TRUE)
    if ($target_enable == TRUE) {

      // Prevent the Default project from being enabled
      // when the project code is not set.
      if (!($target_oid == 1 && $result->project_code == 0)) {

        // Check that the paths are valid for the newly enabled project.
        $valid_paths = PathChecker::validatePaths($target_paths);

        // Check to see if the enable project has path entries that will
        // result in duplicates with other enable projects.
        if ($valid_paths === TRUE) {
          list($unique_path, $target_path) = PathChecker::uniquePaths($target_paths, $target_oid);
          if ($unique_path !== TRUE) {
            $message = t('Project was not enabled due to path setting resulting
                         in duplicate path entries between enabled projects.');
          }
        }
        else {
          $message = t('Project was not enabled due to path setting: @valid_paths
                        resulting in an invalid path.',
                       ['@valid_paths' => $valid_paths]);
        }

      }
      else {
        $default_project = TRUE;
        $message = t('Default project not enabled.
                       Enter Optimizely ID in Account Info page.');
      }

    }

    // The newly enabled project has unique paths OR the target project is
    // currently enabled (TRUE) and will now be disabled.
    if (($target_enable == FALSE || $unique_path === TRUE) && ($default_project == FALSE)) {

      // Toggle $target_enable.
      $target_enable ? $target_enable = 1 : $target_enable = 0;

      // Update database with new enable setting for project entry.
      $results = \Drupal::database()->update('optimizely')
        ->fields([
          'enabled' => (int) $target_enable,
        ])
        ->condition('oid', $target_oid)
        ->execute();

      // Refresh cache on project paths, this includes both enable and disabled
      // projects as there will be a need to clear the js calls in both cases.
      CacheRefresher::doRefresh($target_paths);

      // Tell AJAX request of status to trigger jquery.
      $options = [
        'status' => 'updated',
        'oid' => $target_oid,
        'target_enable' => $target_enable,
        'message' => $message,
      ];

      return new JsonResponse($options);

    }
    else {
      $options = [
        'status' => 'rejected',
        'oid' => $target_oid,
        'issue_path' => $target_path,
        'message' => $message,
      ];

      return new JsonResponse($options);
    }

  }

}
