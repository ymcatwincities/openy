<?php

/**
 * @file
 * Contains \Drupal\libraries\ExternalLibrary\Utility\DependencyAccessorTrait.
 */

namespace Drupal\libraries\ExternalLibrary\Utility;

/**
 * Provides a trait for classes giving access to a library dependency.
 */
trait DependencyAccessorTrait {

  /**
   * The dependency.
   *
   * @var \Drupal\libraries\ExternalLibrary\ExternalLibraryInterface
   */
  protected $dependency;

  /**
   * Returns the dependency.
   *
   * @return \Drupal\libraries\ExternalLibrary\ExternalLibraryInterface
   *   The library.
   */
  public function getLibrary() {
    return $this->dependency;
  }

}
