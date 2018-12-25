<?php

/**
 * @file
 * Post update functions for REST UI.
 */

/**
 * Simplify method-granularity REST resource config to resource-granularity.
 *
 * Re-runs the REST module's update path, because the REST UI module only
 * allowed creating 'method' granularity resources until version 1.14.
 *
 * @see https://www.drupal.org/node/2869443
 * @see https://www.drupal.org/node/2721595
 */
function restui_post_update_resource_granularity() {
  require_once \Drupal::root() . '/core/modules/rest/rest.post_update.php';
  rest_post_update_resource_granularity();
}
