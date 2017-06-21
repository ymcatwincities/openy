<?php

/**
 * @file
 * Provides views data for blazy.module.
 */

/**
 * Implements hook_views_data_alter().
 */
function blazy_views_data_alter(&$data) {
  $data['file_managed']['blazy_file'] = [
    'title' => 'Blazy',
    'help'  => t('Displays a preview of a File using Blazy, if applicable.'),
    'field' => [
      'id' => 'blazy_file',
      'click sortable' => FALSE,
    ],
  ];

  if (function_exists('video_embed_media_media_bundle_insert')) {
    $data['media_field_data']['blazy_media'] = [
      'title' => 'Blazy',
      'help'  => t('Displays a preview of a Media using Blazy, if applicable.'),
      'field' => [
        'id' => 'blazy_media',
        'click sortable' => FALSE,
      ],
    ];
  }
}

/**
 * Implements hook_views_plugins_style_alter().
 */
function blazy_views_plugins_style_alter(array &$plugins) {
  $plugins['blazy'] = [
    'id'             => 'blazy',
    'label'          => 'Blazy Grid',
    'description'    => t('Display the results in a Blazy grid.'),
    'class'          => 'Drupal\blazy\Plugin\views\style\BlazyViews',
    'display_types'  => ['normal'],
    'help'           => t('Works best with Views field containing Blazy.'),
    'parent'         => 'parent',
    'plugin_type'    => 'style',
    'register_theme' => FALSE,
    'short_title'    => 'Blazy',
    'title'          => 'Blazy Grid',
    'provider'       => 'blazy',
  ];
}
