<?php

/**
 * @file
 * Post update functions for File Browser.
 */

/**
 * @addtogroup updates-8.x-1.x
 * @{
 */

/**
 * Update default config with default uuid.
 */
function file_browser_post_update_default_uuid() {
  $configuration = \Drupal::configFactory()->getEditable('embed.button.file_browser');
  // Default uuid in the config.
  $uuid = 'db2cad05-1e3b-4b35-b163-99d7d036130c';
  // Set file uuid in the config.
  $configuration->set('icon_uuid', $uuid);
  $configuration->save();
  // Load the file_browser_icon form the storage.
  $files = \Drupal::entityTypeManager()
    ->getStorage('file')
    ->loadByProperties(['uri' => 'public://file_browser_icon.png']);
  if (!empty($files)) {
    $file = reset($files);
    // Set file uuid same as default config.
    $file->set('uuid', $uuid);
    $file->save();
  }
}

/**
 * @} End of "addtogroup updates-8.x-1.x".
 */
