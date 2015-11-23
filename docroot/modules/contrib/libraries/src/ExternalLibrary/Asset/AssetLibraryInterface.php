<?php

/**
 * @file
 * Contains \Drupal\libraries\ExternalLibrary\Asset\AssetLibraryInterface.
 */

namespace Drupal\libraries\ExternalLibrary\Asset;

use Drupal\libraries\ExternalLibrary\ExternalLibraryInterface;

/**
 * Provides an interface for library with assets.
 *
 * @see \Drupal\libraries\ExternalLibrary\Asset\AssetLibraryTrait
 *
 * @todo Explain
 */
interface AssetLibraryInterface extends ExternalLibraryInterface {

  /**
   * Returns a core asset library array structure for this library.
   *
   * @return array
   *
   * @see libraries_library_info_build()
   * @see \Drupal\libraries\ExternalLibrary\Asset\SingleAssetLibraryTrait
   *
   * @throws \Drupal\libraries\ExternalLibrary\Exception\InvalidLibraryDependencyException
   *
   * @todo Document the return value.
   */
  public function getAttachableAssetLibraries();

}
