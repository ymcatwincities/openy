<?php

namespace Drupal\default_content;

/**
 * A scanner to find YAML files in a given folder.
 */
interface ScannerInterface {

  /**
   * Returns a list of file objects.
   *
   * @param string $directory
   *   Absolute path to the directory to search.
   *
   * @return object[]
   *   List of stdClass objects with name and uri properties.
   */
  public function scan($directory);

}
