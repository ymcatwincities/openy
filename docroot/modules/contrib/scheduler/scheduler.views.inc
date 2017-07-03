<?php

/**
 * @file
 * Views integration file for Scheduler module.
 */

/**
 * Implements hook_views_data().
 */
function scheduler_views_data() {
  $tables['scheduler']['table']['group'] = t('Scheduler');

  // Define how the Scheduler table is linked to the node table.
  $tables['scheduler']['table']['join']['node'] = [
    'left_field' => 'nid',
    'field' => 'nid',
  ];

  // Describe the two fields in the Scheduler database table.
  $tables['scheduler']['publish_on'] = [
    'title' => t('Publish on'),
    'help' => t('Date/time on which the article will be automatically published'),
    'field' => [
      'handler' => 'views_handler_field_date',
      'click sortable' => TRUE,
    ],
    'filter' => [
      'handler' => 'views_handler_filter_date',
      'label' => t('Publish on'),
      'allow empty' => TRUE,
    ],
    'sort' => [
      'handler' => 'views_handler_sort_date',
    ],
  ];

  $tables['scheduler']['unpublish_on'] = [
    'title' => t('Unpublish on'),
    'help' => t('Date/time on which the article will be automatically unpublished'),
    'field' => [
      'handler' => 'views_handler_field_date',
      'click sortable' => TRUE,
    ],
    'filter' => [
      'handler' => 'views_handler_filter_date',
      'label' => t('Unpublish on'),
      'allow empty' => TRUE,
    ],
    'sort' => [
      'handler' => 'views_handler_sort_date',
    ],
  ];

  // Describe the two extra derived fields provided for Views.
  $tables['scheduler']['publish_countdown'] = [
    'title' => t('Publish countdown'),
    'help' => t('Time until the article will be automatically published'),
    'field' => [
      'handler' => 'scheduler_handler_field_scheduler_countdown',
      'click sortable' => FALSE,
      'timestamp_field' => 'publish_on',
    ],
  ];

  $tables['scheduler']['unpublish_countdown'] = [
    'title' => t('Unpublish countdown'),
    'help' => t('Time until the article will be automatically unpublished'),
    'field' => [
      'handler' => 'scheduler_handler_field_scheduler_countdown',
      'click sortable' => FALSE,
      'timestamp_field' => 'unpublish_on',
    ],
  ];

  return $tables;
}

/**
 * Implements hook_views_handlers().
 */
function scheduler_views_handlers() {
  return [
    'handlers' => [
      'scheduler_handler_field_scheduler_countdown' => [
        'parent' => 'views_handler_field',
      ],
    ],
  ];
}
