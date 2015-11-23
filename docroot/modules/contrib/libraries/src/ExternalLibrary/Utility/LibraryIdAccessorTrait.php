<?php

/**
 * @file
 * Contains \Drupal\libraries\ExternalLibrary\Utility\LibraryIdAccessorTrait.
 */

namespace Drupal\libraries\ExternalLibrary\Utility;

/**
 * Provides a trait for classes giving access to a library ID.
 */
trait LibraryIdAccessorTrait {

  /**
   * The library ID of the library that caused the exception.
   *
   * @var string
   */
  protected $libraryId;

  /**
   * Returns the library ID of the library that caused the exception.
   *
   * @return string
   *   The library ID.
   */
  public function getLibraryId() {
    return $this->libraryId;
  }

}
