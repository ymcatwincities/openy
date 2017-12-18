<?php

namespace Drupal\panelizer\Access;

use Drupal\Core\Access\AccessResult;

/**
 * Provides a custom access checking mechanism for default displays.
 */
class PanelizerDefaultsDisplayAccess {

  /**
   * Custom access checker for determining the default display of the bundle.
   *
   * @param string $machine_name
   *   The machine name of the default display.
   *
   * @return \Drupal\Core\Access\AccessResult
   */
  public function isNotDefaultDisplay($machine_name) {
    list($entity_type, $bundle, $view_mode, $default) = explode('__', $machine_name);
    /** @var \Drupal\panelizer\Panelizer $panelizer */
    $panelizer = \Drupal::service('panelizer');
    $settings = $panelizer->getPanelizerSettings($entity_type, $bundle, $view_mode);
    if ($settings['default'] != $default) {
      $access = AccessResult::allowed();
    }
    else {
      $access = AccessResult::forbidden();
    }
    return $access->addCacheTags(["panelizer_default:$entity_type:$bundle:$view_mode", "panelizer_default:$entity_type:$bundle:$view_mode:$default"]);
  }

}
