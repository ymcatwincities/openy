<?php

/**
 * @file
 * File hooks implemented by the File entity module.
 */

use Drupal\file\FileInterface;
use Drupal\file_entity\Entity\FileType;

/**
 * Implements hook_file_type().
 */
function file_entity_file_type(FileInterface $file) {
  $types = array();
  foreach (FileType::loadEnabled() as $type) {
    if (file_entity_match_mimetypes($type->getMimeTypes(), $file->getMimeType())) {
      $types[] = $type->id();
    }
  }

  return $types;
}

/**
 * Implements hook_file_metadata_info().
 */
function file_entity_file_metadata_info() {
  $info['width'] = array('label' => t('Width'), 'type' => 'integer');
  $info['height'] = array('label' => t('Height'), 'type' => 'integer');
  return $info;
}
