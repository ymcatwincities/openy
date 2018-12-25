<?php

/**
 * @file
 * Hooks provided by the Layout Plugin module.
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Allow modules to alter layout plugin definitions.
 *
 * @param array[] $definitions
 *   The array of layout definitions, keyed by plugin ID.
 */
function hook_layout_alter(&$definitions) {
  // Remove a layout.
  unset($definitions['twocol']);
}

/**
 * @} End of "addtogroup hooks".
 */
