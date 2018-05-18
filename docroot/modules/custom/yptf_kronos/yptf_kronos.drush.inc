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
    'arguments' => [
      'request_number' => 'Request number for MB.',
    ],
  ];
  $items['yptf_kronos_generate_monday'] = [
    'description' => 'Generate Email reports for Monday.',
    'callback' => 'drush_yptf_kronos_generate_monday',
    'drupal dependencies' => ['yptf_kronos'],
    'arguments' => [
      'request_number' => 'Request number for MB.',
    ],
    'aliases' => ['ykgm']
  ];
  return $items;
}

/**
 * Callback function for hook_drush_command().
 *
 * Generate email reports.
 *
 * @param int $request_number
 *   Request number to MB.
 */
function drush_yptf_kronos_generate($request_number = 0) {
  \Drupal::service('yptf_kronos_reports.generate')->generateReports($request_number);
}

/**
 * Callback function for hook_drush_command().
 *
 * Generate email reports for monday.
 *
 * @param int $request_number
 *   Request number to MB.
 */
function drush_yptf_kronos_generate_monday($request_number = 0) {
  \Drupal::service('yptf_kronos_reports.generate_monday')->generateReports($request_number);
}
