<?php

use Drupal\Core\Queue\QueueInterface;

/**
 * Implements hook_drush_command().
 */
function queue_ui_drush_command() {
  $items = [];

  $items['queue-process'] = [
    'description' => 'Process queue',
    'arguments' => [
      'id' => "The QueueWorker ID",
    ],
    'aliases' => ['qp'],
  ];
  $items['queue-process-all'] = [
    'description' => 'Process all queues',
    'aliases' => ['qpa'],
  ];
  $items['queue-release'] = [
    'description' => 'Remove lease from queue',
    'arguments' => [
      'id' => "The QueueWorker ID",
    ],
    'aliases' => ['qr'],
  ];
  $items['queue-release-all'] = [
    'description' => 'Remove lease from all queue',
    'aliases' => ['qra'],
  ];

  return $items;
}

/**
 * Process queue
 */
function drush_queue_ui_queue_process($queue_name = NULL) {
  $operations = [];

  // Require the choice name to be filled
  if ($queue_name = _queue_ui_queue_choice($queue_name)) {
    // Add operations and start to batch process
    _queue_ui_add_operations($operations, $queue_name);
    _queue_ui_start_batch($operations);
  }
}

/**
 * Process all queues
 */
function drush_queue_ui_queue_process_all() {
  $operations = [];

  // Add operations for each queue
  foreach (queue_ui_defined_queues() as $queue_name => $queue_definition) {
    _queue_ui_add_operations($operations, $queue_name);
  }

  // Start batch process
  _queue_ui_start_batch($operations);
}

/**
 * Remove lease from queue
 */
function drush_queue_ui_queue_release($queue_name = NULL) {
  // Require the choice name to be filled
  if ($queue_name = _queue_ui_queue_choice($queue_name)) {
    _queue_ui_release_queue($queue_name);
  }
}

/**
 * Remove lease from all queues
 */
function drush_queue_ui_queue_release_all() {
  // Release each queue
  foreach (queue_ui_defined_queues() as $queue_name => $queue_definition) {
    _queue_ui_release_queue($queue_name);
  }
}

/**
 * Remove leases from all items in a queue
 *
 * @param $queue_name
 */
function _queue_ui_release_queue($queue_name) {
  $num_updated = \Drupal::service('database')->update('queue')
    ->fields(array(
      'expire' => 0,
    ))
    ->condition('name', $queue_name, '=')
    ->execute();

  drush_log(t('@count lease reset in queue @name', array('@count' => $num_updated, '@name' => $queue_name)), 'ok');
}

/**
 * Give the user a choice prompt
 *
 * @param $queue_name
 * @return mixed
 */
function _queue_ui_queue_choice($queue_name) {
  // Queue name is not provided
  if (empty($queue_name)) {
    // Get all defined queue names
    $defined_queues = queue_ui_defined_queues();
    $queue_names = array_keys($defined_queues);
    // Show a list of all defined queues
    $choice = drush_choice($queue_names, t('Which queue do you want to process?'));
    if (is_numeric($choice)) {
      $queue_name = $queue_names[$choice];
    }
  }

  return $queue_name;
}

/**
 * Populate the operations
 *
 * @param $operations
 * @param $queue_name
 */
function _queue_ui_add_operations(&$operations, $queue_name) {
  /** @var QueueInterface $queue */
  $queue = \Drupal::service('queue')->get($queue_name);

  if ($numberOfItems = $queue->numberOfItems()) {
    foreach (range(1, $numberOfItems) as $index) {
      $operations[] = ['\Drupal\queue_ui\QueueUIBatch::step', [$queue_name]];
    }
  }
}

/**
 * Helper function to start a batch process
 *
 * @param $operations
 */
function _queue_ui_start_batch($operations) {
  $batch = [
    'operations' => $operations,
  ];

  // Set and configure the batch.
  batch_set($batch);
  $batch =& batch_get();
  $batch['progressive'] = FALSE;

  // Process the batch.
  drush_backend_batch_process();
}
