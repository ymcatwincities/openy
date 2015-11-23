<?php

/**
 * @file
 * Contains \Drupal\libraries\ExternalLibrary\PhpFile\PhpFileLibrary.
 */

namespace Drupal\libraries\ExternalLibrary\PhpFile;
use Drupal\libraries\ExternalLibrary\ExternalLibraryTrait;
use Drupal\libraries\ExternalLibrary\LocalLibraryTrait;

/**
 * Provides a base PHP file library implementation.
 */
class PhpFileLibrary implements PhpFileLibraryInterface {

  use ExternalLibraryTrait;
  use LocalLibraryTrait;

  /**
   * Constructs a PHP file library.
   *
   * @param string $id
   *   The library ID.
   * @param array $definition
   *   The library definition array parsed from the definition JSON file.
   *
   * @todo Dependency injection
   */
  public function __construct($id, array $definition) {
    $this->id = (string) $id;
    // @todo Split this into proper properties.
    $this->definition = $definition;

    $this->fileSystemHelper = \Drupal::service('file_system');
  }

  /**
   * {@inheritdoc}
   */
  protected function getScheme() {
    return 'php-file';
  }

  /**
   * {@inheritdoc}
   */
  public function getPhpFiles() {
    // @todo
    return $this->definition['files'];
  }

}
