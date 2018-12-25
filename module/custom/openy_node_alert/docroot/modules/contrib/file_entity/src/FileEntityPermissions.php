<?php

namespace Drupal\file_entity;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\file_entity\Entity\FileType;

/**
 * Contains permission callbacks.
 */
class FileEntityPermissions {

  use StringTranslationTrait;

  /**
   * Adds descriptions about stream wrappers to permissions.
   *
   * Descriptions are added to the 'View file details' and 'View own private
   * file details' permissions to show which stream wrappers they apply to.
   */
  public function extendPermissionDetails() {
    $wrappers = file_entity_get_public_and_private_stream_wrapper_names() + array(
      'public' => array($this->t('None')),
      'private' => array($this->t('None')),
    );

    $permissions = array();
    $permissions['view files']['description'] = $this->t('Includes the following stream wrappers: %wrappers.', array('%wrappers' => implode(', ', $wrappers['public'])));
    $permissions['view own private files']['description'] = $this->t('Includes the following stream wrappers: %wrappers.', array('%wrappers' => implode(', ', $wrappers['private'])));
    return $permissions;
  }

  /**
   * Generates standard file permissions for all applicable file types.
   *
   * @return array
   *   File type permissions.
   */
  public function fileTypePermissions() {
    // Generate standard file permissions for all applicable file types.
    $permissions = array();
    foreach (FileType::loadEnabled() as $type) {
      /** @var \Drupal\file_entity\Entity\FileType $type */
      $id = $type->id();
      $permissions += array(
        "edit own $id files" => array(
          'title' => $this->t('%type_name: Edit own files', array('%type_name' => $type->label())),
        ),
        "edit any $id files" => array(
          'title' => $this->t('%type_name: Edit any files', array('%type_name' => $type->label())),
        ),
        "delete own $id files" => array(
          'title' => $this->t('%type_name: Delete own files', array('%type_name' => $type->label())),
        ),
        "delete any $id files" => array(
          'title' => $this->t('%type_name: Delete any files', array('%type_name' => $type->label())),
        ),
        "download own $id files" => array(
          'title' => $this->t('%type_name: Download own files', array('%type_name' => $type->label())),
        ),
        "download any $id files" => array(
          'title' => $this->t('%type_name: Download any files', array('%type_name' => $type->label())),
        ),
      );
    }
    return $permissions;
  }

}
