<?php

/**
 * @file
 * Hooks specific to the Panels IPE module.
 */

use \Drupal\panels\Plugin\DisplayVariant\PanelsDisplayVariant;

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Act on a Panels Display before it is saved via the IPE.
 *
 * @param \Drupal\panels\Plugin\DisplayVariant\PanelsDisplayVariant $panels_display
 *   The current Panels display.
 * @param array $layout_model
 *   The decoded LayoutModel from our App.
 */
function hook_panels_ipe_panels_display_presave(PanelsDisplayVariant $panels_display, array $layout_model) {
  if (isset($layout_model['use_custom_storage'])) {
    $configuration = $panels_display->getConfiguration();
    $panels_display->setStorage('custom_storage_key', $configuration['storage_id']);
  }
}

/**
 * Modify the list of blocks available through the IPE interface.
 *
 * @param array $blocks
 *   The blocks that are currently available.
 */
function hook_panels_ipe_blocks_alter(array &$blocks = array()) {
  // Only show blocks that were provided by the 'mymodule' module.
  foreach ($blocks as $key => $block) {
    if ($block['provider'] !== 'mymodule') {
      unset($blocks[$key]);
    }
  }
}
