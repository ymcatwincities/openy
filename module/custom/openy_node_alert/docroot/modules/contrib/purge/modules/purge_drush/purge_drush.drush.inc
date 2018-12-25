<?php

/**
 * @file
 * Administrative Drush commands for Purge.
 */

use Drupal\Core\DependencyInjection\ContainerNotInitializedException;

/**
 * Implements hook_drush_help().
 */
function purge_drush_drush_help($command) {
  switch ($command) {
    case 'meta:purge_drush:title':
      return dt('Purge');
    case 'meta:purge_drush:summary':
      return dt('Administrative Drush commands for Purge.');
  }
}

/**
 * Implements hook_drush_command().
 */
function purge_drush_drush_command() {
  $items = [];
  $items['p-diagnostics'] = [
    'description' => 'Generate a diagnostic self-service report.',
    'callback' => 'purge_drush_callback_router',
    'callback services' => ['purge.diagnostics',],
    'arguments' => [],
    'required-arguments' => 0,
    'options' => [],
    'examples' => [
      'drush p-diagnostics' => 'Build the diagnostic report as a table.',
      'drush p-diagnostics --format=json' => 'Export as JSON.',
      'drush p-diagnostics --format=yaml' => 'Export as YAML.',
    ],
    '_outputformats' => ['table', 'yaml', 'json',],
    'outputformat' => [
      'default' => 'table',
      'pipe-format' => 'yaml',
      'concatenate-columns' => ['recommendation' => ['value', 'recommendation']],
      'fields-default' => ['title', 'recommendation', 'severity'],
      'field-labels' => [
        'id' => 'Id',
        'title' => 'Title',
        'description' => 'Description',
        'recommendation' => 'Recommendation',
        'value' => 'Value',
        'severity' => 'Severity',
        'severity_int' => 'SevInt',
        'blocks_processing' => 'Blocking?',
      ],
      'column-widths' => [
        'severity' => 8,
        'value' => 20,
        'recommendation' => 50,
        'severity_int' => 6,
      ],
    ],
    'aliases' => ['pdia'],
    'core' => ['8+'],
  ];
  $items['p-invalidate'] = [
    'description' => 'Directly invalidate an item without going through the queue.',
    'callback' => 'purge_drush_callback_router',
    'callback services' => ['purge.invalidation.factory', 'purge.processors', 'purge.purgers'],
    'arguments' => [
      'type' => 'The type of invalidation to perform, e.g.: tag, path, url.',
      'expression' => 'The string expression of what needs to be invalidated.',
    ],
    'required-arguments' => 1,
    'options' => [],
    'examples' => [
      'drush p-invalidate tag node:1' => 'Clears URLs tagged with "node:1" from external caching platforms.',
      'drush p-invalidate url http://www.drupal.org/' => 'Clears "http://www.drupal.org/" from external caching platforms.',
      'drush p-invalidate everything' => 'Clears everything on external caching platforms.',
    ],
    '_outputformats' => ['string', 'yaml', 'json'],
    'outputformat' => [
      'default' => 'string',
      'pipe-format' => 'yaml',
    ],
    'aliases' => ['pinv'],
    'core' => ['8+'],
  ];
  $items['p-processors'] = [
    'description' => 'List all enabled processors.',
    'callback' => 'purge_drush_callback_router',
    'callback services' => ['purge.processors',],
    'arguments' => [],
    'required-arguments' => FALSE,
    'options' => [],
    'examples' => [
      'drush p-processor-list' => 'List all processors in a table.',
      'drush p-processor-list --table=json' => 'Export all processors in JSON.',
      'drush p-processor-list --table=yaml' => 'Export all processors in YAML.',
    ],
    '_outputformats' => ['table', 'yaml', 'json', 'list'],
    'outputformat' => [
      'default' => 'table',
      'pipe-format' => 'list',
      'field-labels' => [
        'id' => 'Id',
        'label' => 'Label',
        'description' => 'Description',
      ],
    ],
    'aliases' => ['ppro'],
    'core' => ['8+'],
  ];
  $items['p-purgers'] = [
    'description' => 'List all configured purgers in order of execution.',
    'callback' => 'purge_drush_callback_router',
    'callback services' => ['purge.purgers'],
    'arguments' => [],
    'required-arguments' => 0,
    'options' => [],
    'examples' => [
      'drush p-purgers' => 'List all configured purgers in order of execution.',
      'drush p-purgers --format=json' => 'Export as JSON.',
      'drush p-purgers --format=yaml' => 'Export as YAML.',
    ],
    '_outputformats' => ['table', 'yaml', 'json', 'list'],
    'outputformat' => [
      'default' => 'table',
      'pipe-format' => 'list',
      'field-labels' => [
        'instance_id' => 'Instance',
        'plugin_id' => 'Plugin',
        'label' => 'Label',
        'description' => 'Description',
      ],
    ],
    'aliases' => ['ppu'],
    'core' => ['8+'],
  ];
  $items['p-purgers-available'] = [
    'description' => 'List all plugin IDs for which purgers can be created.',
    'callback' => 'purge_drush_callback_router',
    'callback services' => ['purge.purgers'],
    'arguments' => [],
    'required-arguments' => 0,
    'options' => [],
    'examples' => [
      'drush p-purgers' => 'List all plugin IDs for which purgers can be created.',
      'drush p-purgers --format=json' => 'Export as JSON.',
      'drush p-purgers --format=yaml' => 'Export as YAML.',
    ],
    '_outputformats' => ['table', 'yaml', 'json', 'list'],
    'outputformat' => [
      'default' => 'table',
      'pipe-format' => 'list',
      'field-labels' => [
        'plugin_id' => 'Plugin',
        'label' => 'Label',
        'description' => 'Description',
      ],
    ],
    'aliases' => ['ppua'],
    'core' => ['8+'],
  ];
  $items['p-purger-add'] = [
    'description' => 'Create a new purger instance.',
    'callback' => 'purge_drush_callback_router',
    'callback services' => ['purge.purgers'],
    'arguments' => [
      'id' => 'The plugin ID of the purger instance to create.',
    ],
    'required-arguments' => 1,
    'options' => [
      'if-not-exists' => [
        'description' => "Don't create a new purger if one of this type exists.",
        'value' => FALSE,
        '_default' => FALSE,
      ],
    ],
    'examples' => [
      'drush p-purger-add ID' => 'Add a purger of type ID.',
      'drush p-purger-add --if-not-exists ID' => 'Create purger ID if it does not exist.',
    ],
    '_outputformats' => ['string', 'yaml', 'json'],
    'outputformat' => [
      'default' => 'string',
      'pipe-format' => 'yaml',
    ],
    'aliases' => ['ppa'],
    'core' => ['8+'],
  ];
  $items['p-purger-rm'] = [
    'description' => 'Remove a purger instance.',
    'callback' => 'purge_drush_callback_router',
    'callback services' => ['purge.purgers'],
    'arguments' => [
      'id' => 'The instance ID of the purger to remove.',
    ],
    'required-arguments' => 1,
    'options' => [],
    'examples' => [
      'drush p-purger-remove ID' => 'Remove the given purger.',
    ],
    '_outputformats' => ['string', 'yaml', 'json'],
    'outputformat' => [
      'default' => 'string',
      'pipe-format' => 'yaml',
    ],
    'aliases' => ['ppr'],
    'core' => ['8+'],
  ];
  $items['p-queue-add'] = [
    'description' => 'Schedule an item for later processing.',
    'callback' => 'purge_drush_callback_router',
    'callback services' => ['purge.queue', 'purge.queuers', 'purge.invalidation.factory'],
    'arguments' => [
      'type' => 'The type of invalidation to queue, e.g.: tag, path, url.',
      'expression' => 'The string expression of what needs to be invalidated.',
    ],
    'required-arguments' => 1,
    'options' => [],
    'examples' => [
      'drush p-queue-add tag node:1' => 'Clears URLs tagged with "node:1" from external caching platforms.',
      'drush p-queue-add url http://www.drupal.org/' => 'Clears "http://www.drupal.org/" from external caching platforms.',
    ],
    '_outputformats' => ['string', 'yaml', 'json'],
    'outputformat' => [
      'default' => 'string',
      'pipe-format' => 'yaml',
    ],
    'aliases' => ['pqa'],
    'core' => ['8+'],
  ];
  $items['p-queue-browse'] = [
    'description' => 'Inspect what is in the queue by paging through it.',
    'callback' => 'purge_drush_callback_router',
    'callback services' => ['purge.queue',],
    'arguments' => [],
    'required-arguments' => FALSE,
    'options' => [
      'limit' => [
        'description' => 'The number of items to show on a single page.',
        'example-value' => '20',
        'value' => 'optional',
        '_default' => 20,
      ],
      'page' => [
        'description' => 'The page to show data for, pages start at 1.',
        'example-value' => '1',
        'value' => 'optional',
        '_default' => 1,
      ],
    ],
    'examples' => [
      'drush p-queue-browse' => 'Browse queue content and press space to load more.',
      'drush p-queue-browse --limit=30' => 'Browse the queue content and show 30 items at a time.',
      'drush p-queue-browse --page=3' => 'Show page 3 of the queue.',
      'drush p-queue-browse --format=json' => 'Fetch a page from the queue exported as JSON.',
      'drush p-queue-browse --format=yaml' => 'Fetch a page from the queue exported as JSON.',
    ],
    '_outputformats' => ['table', 'yaml', 'json', 'list'],
    'outputformat' => [
      'default' => 'table',
      'pipe-format' => 'list',
      'field-labels' => [
        'typetr' => 'Type',
        'statetr' => 'State',
        'expression' => 'Expression',
      ],
    ],
    'aliases' => ['pqb'],
    'core' => ['8+'],
  ];
  $items['p-queue-empty'] = [
    'description' => 'Clear the queue and reset all statistics.',
    'callback' => 'purge_drush_callback_router',
    'callback services' => ['purge.queue.stats', 'purge.queue',],
    'arguments' => [],
    'required-arguments' => FALSE,
    'options' => [],
    'examples' => [
      'drush p-queue-empty' => 'Clear the queue and reset all statistics.',
    ],
    '_outputformats' => ['string', 'yaml', 'json'],
    'outputformat' => [
      'default' => 'string',
      'pipe-format' => 'yaml',
    ],
    'aliases' => ['pqe'],
    'core' => ['8+'],
  ];
  $items['p-queue-stats'] = [
    'description' => 'Retrieve the queue statistics.',
    'callback' => 'purge_drush_callback_router',
    'callback services' => ['purge.queue.stats',],
    'arguments' => [],
    'required-arguments' => FALSE,
    'options' => [],
    'examples' => [
      'drush p-queue-stats' => 'Retrieve the queue statistics.',
      'drush p-queue-stats --format=json' => 'Fetch the data as JSON.',
      'drush p-queue-stats --format=yaml' => 'Fetch the data as YAML.',
    ],
    '_outputformats' => ['table', 'yaml', 'json',],
    'outputformat' => [
      'default' => 'table',
      'pipe-format' => 'yaml',
      'field-labels' => [
        'title' => '',
        'description' => 'Description',
        'value' => 'Value',
      ],
      'column-widths' => [
        'title' => 15,
        'value' => 10,
        'description' => 40,
      ],
    ],
    'aliases' => ['pqs'],
    'core' => ['8+'],
  ];
  $items['p-queue-work'] = [
    'description' => 'Claim a chunk of items from the queue and process them.',
    'callback' => 'purge_drush_callback_router',
    'callback services' => ['purge.queue', 'purge.processors', 'purge.purgers'],
    'arguments' => [],
    'required-arguments' => FALSE,
    'options' => [],
    'examples' => [
      'drush p-queuer-workchunk' => 'Claim a chunk of items from the queue and process them.',
    ],
    '_outputformats' => ['string', 'yaml', 'json'],
    'outputformat' => [
      'default' => 'string',
      'pipe-format' => 'yaml',
    ],
    'aliases' => ['pqw'],
    'core' => ['8+'],
  ];
  $items['p-queuers'] = [
    'description' => 'List all enabled queuers.',
    'callback' => 'purge_drush_callback_router',
    'callback services' => ['purge.queuers',],
    'arguments' => [],
    'required-arguments' => FALSE,
    'options' => [],
    'examples' => [
      'drush p-queuer-list' => 'List all queuers in a table.',
      'drush p-queuer-list --table=json' => 'Export all queuers in JSON.',
      'drush p-queuer-list --table=yaml' => 'Export all queuers in YAML.',
    ],
    '_outputformats' => ['table', 'yaml', 'json', 'list'],
    'outputformat' => [
      'default' => 'table',
      'pipe-format' => 'list',
    ],
    'aliases' => ['pqrs'],
    'core' => ['8+'],
  ];
  $items['p-types'] = [
    'description' => 'List all supported cache invalidation types.',
    'callback' => 'purge_drush_callback_router',
    'callback services' => ['purge.invalidation.factory', 'purge.purgers'],
    'arguments' => [],
    'required-arguments' => 0,
    'options' => [],
    'examples' => [
      'drush p-types' => 'List all supported cache invalidation types.',
      'drush p-types --format=json' => 'Export as JSON.',
      'drush p-types --format=yaml' => 'Export as YAML.',
    ],
    '_outputformats' => ['table', 'yaml', 'json', 'list'],
    'outputformat' => [
      'default' => 'table',
      'pipe-format' => 'list',
      'field-labels' => ['label' => 'Type',],
    ],
    'aliases' => ['ptyp'],
    'core' => ['8+'],
  ];
  return $items;
}

/**
 * Implements hook_drush_command_alter().
 */
function purge_drush_drush_command_alter(&$command) {

  // P-TYPES: Add a column for each enabled purger.
  if ($command['command'] === 'p-types') {
    try {
      foreach (\Drupal::service('purge.purgers')->getLabels() as $id => $label) {
        $label = (string) $label;
        $command['outputformat']['field-labels'][$id] = $label;
        $command['engines']['outputformat']['field-labels'][$id] = $label;
      }
    }
    catch (ContainerNotInitializedException $e) {
      // This happens barely, but when it does we simply don't add any columns.
    }
  }
}

/**
 * Route command execution to the right function and load its file.
 *
 * @param mixed $...
 *   One or more parameters passed depending on the routed command definition.
 *
 * @return mixed
 *   The command response.
 */

function purge_drush_callback_router() {
  $context = drush_get_command();
  $command = str_replace('-', '_', $context['command']);

  // Load requested container services in our own 'callback services' field.
  $services = [];
  foreach ($context['callback services'] as $service) {
    $services[] = \Drupal::service($service);
  }

  // Append options with '_default' as arguments to keep callbacks simple.
  $args = func_get_args();
  foreach ($context['options'] as $option => $definition) {
    if (isset($definition['_default'])) {
      $args[] = drush_get_option($option, $definition['_default']);
    }
  }

  // Verify that the given --format is listed in our '_outputformats'.
  if (drush_get_option('pipe', FALSE)) {
    $format = $context['outputformat']['pipe-format'];
  }
  else {
    $format = drush_get_option('format', $context['outputformat']['default']);
  }
  if (!in_array($format, $context['_outputformats'])) {
    $formats = ['@formats' => implode(', ', $context['_outputformats'])];
    $message = dt('Unsupported output format, options: @formats.', $formats);
    return drush_set_error('DRUSH_FORMAT_ERROR', $message);
  }

  // Require the actual file in which the callback resides.
  require_once dirname(__FILE__) . '/' .  'purge_drush.' . $command . '.inc';

  // Build up the final structure of the arguments and dispatch code execution.
  $final_args = array_merge(
    [$format],
    $services, $args);
  return call_user_func_array('purge_drush_' . $command, $final_args);
}
