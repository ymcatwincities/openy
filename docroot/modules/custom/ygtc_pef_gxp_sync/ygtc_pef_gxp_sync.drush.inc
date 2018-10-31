<?php

/**
 * @file
 * Drush integration.
 */

/**
 * Implements hook_drush_command().
 */
function ygtc_pef_gxp_sync_drush_command() {
  $items['ygtc-pef-gxp-sync'] = [
    'description' => 'Run YGTC PEF GXP Sync.',
    'callback' => 'drush_ygtc_pef_gxp_sync',
  ];
  return $items;
}

/**
 * Run syncer.
 */
function drush_ygtc_pef_gxp_sync() {
  ygtc_pef_gxp_sync_run();
}
