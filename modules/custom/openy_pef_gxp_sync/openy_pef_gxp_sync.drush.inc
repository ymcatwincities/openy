<?php

/**
 * @file
 * Drush integration.
 */

/**
 * Implements hook_drush_command().
 */
function openy_pef_gxp_sync_drush_command() {
  $items['openy-pef-gxp-sync'] = [
    'description' => 'Run YGTC PEF GXP Sync.',
    'callback' => 'drush_openy_pef_gxp_sync',
  ];
  return $items;
}

/**
 * Run syncer.
 */
function drush_openy_pef_gxp_sync() {
  openy_pef_gxp_sync_run();
}
