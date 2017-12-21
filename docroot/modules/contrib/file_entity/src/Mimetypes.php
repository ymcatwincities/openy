<?php

namespace Drupal\file_entity;
use Drupal\Core\File\MimeType\ExtensionMimeTypeGuesser;

/**
 * Overrides a MIME type guesser to provide a public list of MIME types.
 *
 * @todo remove if https://www.drupal.org/node/1921558#comment-9007545 agree on
 * a fix.
 */
class Mimetypes extends ExtensionMimeTypeGuesser {

  /**
   * Get MIME types.
   *
   * @return array
   *   An associative array of MIME types, keyed by extensions.
   */
  public function get() {
    if ($this->mapping === NULL) {
      $mapping = $this->defaultMapping;
      // Allow modules to alter the default mapping.
      $this->moduleHandler->alter('file_mimetype_mapping', $mapping);
      $this->mapping = $mapping;
    }
    return $this->mapping['mimetypes'];
  }
}
