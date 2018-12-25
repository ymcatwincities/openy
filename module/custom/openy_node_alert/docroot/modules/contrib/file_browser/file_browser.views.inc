<?php

/**
 * @file
 * Provide custom views data for rendering file previews.
 */

/**
 * Implements hook_views_data_alter().
 */
function file_browser_views_data_alter(&$data) {
  $data['file_managed']['file_browser_preview'] = array(
    'title' => t('File Browser Preview'),
    'help' => t('Displays a preview of a file.'),
    'field' => array(
      'id' => 'file_browser_preview',
    ),
  );
}
