<?php

/**
 * @file
 * Drush integration.
 */

/**
 * Implements hook_drush_command().
 */
function yptf_kronos_drush_command() {
  $items['yptf_kronos_generate_tue'] = [
    'description' => 'Generate Tuesday Email reports.',
    'callback' => 'drush_yptf_kronos_generate_tue',
    'drupal dependencies' => ['yptf_kronos'],
    'aliases' => ['ykgt']
  ];
  $items['yptf_kronos_generate_mon'] = [
    'description' => 'Generate Monday Email reports.',
    'callback' => 'drush_yptf_kronos_generate_mon',
    'drupal dependencies' => ['yptf_kronos'],
    'aliases' => ['ykgm']
  ];
  return $items;
}

/**
 * Generate Tuesday email reports.
 */
function drush_yptf_kronos_generate_tue() {
  \Drupal::service('yptf_kronos_reports.generate')->generateReports('tuesday');
}

/**
 * Generate Monday email reports.
 */
function drush_yptf_kronos_generate_mon() {
  \Drupal::service('yptf_kronos_reports.generate')->generateReports('monday');
}
