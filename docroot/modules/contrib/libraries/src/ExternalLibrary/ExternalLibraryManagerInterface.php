<?php

/**
 * @file
 * Contains \Drupal\libraries\ExternalLibrary\ExternalLibraryManagerInterface.
 */

namespace Drupal\libraries\ExternalLibrary;


/**
 * Provides an interface for external library managers.
 */
interface ExternalLibraryManagerInterface {

  /**
   * Gets the list of libraries that are required by enabled extensions.
   *
   * Modules, themes, and installation profiles can declare library dependencies
   * in their info files.
   *
   * @return \Drupal\libraries\ExternalLibrary\ExternalLibraryInterface[]|\Generator
   *   An array of libraries keyed by their ID.
   *
   * @todo Expand the documentation.
   * @todo Consider returning just library IDs.
   */
  public function getRequiredLibraries();

  /**
   * Loads library files for a library.
   *
   * @param string $id
   *   The ID of the library.
   *
   * @throws \Drupal\libraries\ExternalLibrary\Exception\LibraryClassNotFoundException
   * @throws \Drupal\libraries\ExternalLibrary\Exception\LibraryDefinitionNotFoundException
   * @throws \Drupal\libraries\ExternalLibrary\Exception\LibraryNotInstalledException
   */
  public function load($id);

}
