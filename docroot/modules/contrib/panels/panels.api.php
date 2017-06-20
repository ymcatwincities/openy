<?php

/**
 * @file
 * Hooks provided by Panels.
 */

use \Drupal\panels\Plugin\DisplayVariant\PanelsDisplayVariant;

/**
 * Allow modules to alter the built Panels output.
 *
 * @param array &$build
 *   The fully built render array.
 * @param \Drupal\panels\Plugin\DisplayVariant\PanelsDisplayVariant $panels_display
 *   The Panels display that was rendered.
 */
function hook_panels_build_alter(array &$build, PanelsDisplayVariant $panels_display) {
  $build['extra'] = [
    '#markup' => '<div>Some extra markup</div>',
  ];
}
