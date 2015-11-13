<?php

/**
 * @file
 * Theme functions for the module.
 */

/**
 * Prepares variables for sidebar navigation block.
 *
 * @param array $variables
 *   An associative array of variables.
 */
function template_preprocess_sidebar_navigation_block(&$variables) {
  $variables['attributes']['class'][] = 'contextual-region';
}
