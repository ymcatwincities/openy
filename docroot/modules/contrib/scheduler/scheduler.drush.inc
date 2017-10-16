<?php

/**
 * @file
 * Drush commands for Scheduler.
 */

/**
 * Implements hook_drush_command().
 */
function scheduler_drush_command() {
  $items = [];

  $items['scheduler-cron'] = [
    'description' => 'Lighweight cron to process scheduler tasks.',
    'core' => ['8+'],
    'aliases' => ['sch-cron'],
    'category' => 'scheduler',
    'options' => [
      'nomsg' => 'to avoid the "cron completed" message being written to the terminal.',
    ],
  ];

  return $items;
}

/**
 * Run lighweight scheduler cron.
 */
function drush_scheduler_cron() {
  \Drupal::service('scheduler.manager')->runLightweightCron();
  $nomsg = drush_get_option('nomsg', NULL);
  $nomsg ? NULL : drupal_set_message(t('Scheduler lightweight cron completed'));
}
