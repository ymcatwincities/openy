<?php

/**
 * @file
 * Contains \Drupal\libraries\ExternalLibrary\Utility\LibraryAccessorTrait.
 */

namespace Drupal\libraries\ExternalLibrary\Utility;

/**
 * Provides a trait for classes giving access to a library.
 */
trait LibraryAccessorTrait {

  /**
   * The library.
   *
   * @var \Drupal\libraries\ExternalLibrary\ExternalLibraryInterface
   */
  protected $library;

  /**
   * Returns the library.
   *
   * @return \Drupal\libraries\ExternalLibrary\ExternalLibraryInterface
   *   The library.
   */
  public function getLibrary() {
    return $this->library;
  }

}
