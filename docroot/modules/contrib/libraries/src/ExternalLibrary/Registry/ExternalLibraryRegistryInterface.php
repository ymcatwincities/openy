<?php

/**
 * @file
 * Contains \Drupal\libraries\ExternalLibrary\Registry\ExternalLibraryRegistryInterface.
 */

namespace Drupal\libraries\ExternalLibrary\Registry;


/**
 * Provides an interface for library registries.
 */
interface ExternalLibraryRegistryInterface {

  /**
   * Gets a library by its ID.
   *
   * @param string $id
   *   The library ID.
   *
   * @return \Drupal\libraries\ExternalLibrary\ExternalLibraryInterface
   *   The library.
   *
   * @throws \Drupal\libraries\ExternalLibrary\Exception\LibraryClassNotFoundException
   * @throws \Drupal\libraries\ExternalLibrary\Exception\LibraryDefinitionNotFoundException
   */
  public function getLibrary($id);

}
