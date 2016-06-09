<?php

/**
 * @file
 * Provide custom views data for rendering content previews.
 */

/**
 * Implements hook_views_data_alter().
 */
function content_browser_views_data_alter(&$data) {
  // For now only support Nodes, eventually loop through all known Content
  // Entity types.
  $data['node']['content_browser_preview'] = array(
    'title' => t('Content Browser Preview'),
    'help' => t('Displays a rendered preview of the content.'),
    'field' => array(
      'id' => 'content_browser_preview',
    ),
  );
}
