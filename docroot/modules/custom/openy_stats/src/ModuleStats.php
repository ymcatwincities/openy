<?php

namespace Drupal\openy_stats;

/**
 * Class ModuleStats.
 *
 * @package Drupal\openy_stats
 */
class ModuleStats {

  /**
   * Get module list.
   *
   * @return array
   *   Module list.
   */
  public function getModuleList() {
    $moduleHandler = \Drupal::service('module_handler');
    $moduleHandler->loadAll();
    $enabledModules = [];
    foreach ($moduleHandler->getModuleList() as $name => $data) {
      $enabledModules[$name] = ['status' => TRUE];
    }
    return $enabledModules;
  }

}
