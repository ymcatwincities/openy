<?php

namespace Drupal\file_entity\Plugin\views\argument;

use Drupal\file_entity\Entity\FileType;
use Drupal\views\Plugin\views\argument\StringArgument;

/**
 * Argument handler to accept a file type.
 *
 * * @ViewsArgument("file_type")
 */
class Type extends StringArgument {

  /**
   * {@inheritdoc}
   */
  function summaryName($data) {
    return $this->fileType($data->{$this->name_alias});
  }

  /**
   * {@inheritdoc}
   */
  function title() {
    return $this->fileType($this->argument);
  }

  /**
   * Helper function to return the human-readable type of the file.
   */
  function fileType($type) {
    if ($file_entity = FileType::load($type)) {
      return $file_entity->label();
    }
    else {
      return t('Undefined');
    }
  }
}
