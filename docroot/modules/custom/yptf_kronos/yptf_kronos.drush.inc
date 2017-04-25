<?php

/**
 * @file
 * Drush integration.
 */

/**
 * Implements hook_drush_command().
 */
function yptf_kronos_drush_command() {
  $items['yptf_kronos-generate'] = [
    'description' => 'Generate Email reports.',
    'callback' => 'drush_yptf_kronos_generate',
    'drupal dependencies' => ['yptf_kronos'],
  ];
  return $items;
}

/**
 * Callback function for hook_drush_command().
 *
 * Regenerate reports.
 */
function drush_yptf_kronos_generate() {
  \Drupal::service('yptf_kronos_reports.generate')->generateReports();
}
