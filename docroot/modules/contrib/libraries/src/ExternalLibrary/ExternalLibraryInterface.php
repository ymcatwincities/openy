<?php

/**
 * @file
 * Contains \Drupal\libraries\ExternalLibrary\ExternalLibraryInterface.
 */

namespace Drupal\libraries\ExternalLibrary;


/**
 * Provides an interface for different types of external libraries.
 */
interface ExternalLibraryInterface {

  /**
   * Returns the ID of the library.
   *
   * @return string
   *   The library ID. This must be unique among all known libraries.
   *
   * @todo Define what constitutes a "known" library.
   */
  public function getId();

  /**
   * Returns the currently installed version of the library.
   *
   * @return string
   *   The version string, for example 1.0, 2.1.4, or 3.0.0-alpha5.
   */
  public function getVersion();

  /**
   * Returns the libraries dependencies, if any.
   *
   * @return array
   *   An array of library IDs of libraries that the library depends on.
   */
  public function getDependencies();

  /**
   * Creates an instance of the library from its definition.
   *
   * @param string $id
   *   The library ID.
   * @param array $definition
   *   The library definition array parsed from the definition JSON file.
   *
   * @return static
   *
   * @todo Consider passing in some stuff that might be useful.
   */
  public static function create($id, array $definition);

}
