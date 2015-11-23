<?php

/**
 * @file
 * Contains \Drupal\libraries\ExternalLibrary\LocalLibraryInterface.
 */

namespace Drupal\libraries\ExternalLibrary;


/**
 * Provides an interface for local libraries.
 *
 * @todo Explain
 */
interface LocalLibraryInterface extends ExternalLibraryInterface {

  /**
   * Checks whether the library is installed.
   *
   * @return bool
   *   TRUE if the library is installed; FALSE otherwise;
   */
  public function isInstalled();

  /**
   * Gets the path to the library.
   *
   * @return string
   *   The path to the library.
   *
   * @throws \Drupal\libraries\ExternalLibrary\Exception\LibraryNotInstalledException
   */
  public function getLibraryPath();

}
