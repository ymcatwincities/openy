<?php

/**
 * @file Contains Plugin hook documentation.
 */

/**
 * Alters plugin selector plugin definitions.
 *
 * @param array[] $definitions
 *   Keys are plugin IDs. Values are plugin definitions.
 */
function hook_plugin_selector_alter(array &$definitions) {
  // Remove a plugin entirely.
  unset($definitions['foo_plugin_id']);

  // Replace a plugin's class with another.
  $definitions['foo_plugin_id']['class'] = FooPlugin::class;
}
