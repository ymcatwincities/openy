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
