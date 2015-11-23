<?php

/**
 * @file
 * Contains \Drupal\libraries\ExternalLibrary\Asset\AssetLibraryTrait.
 */

namespace Drupal\libraries\ExternalLibrary\Asset;

use Drupal\libraries\ExternalLibrary\Exception\InvalidLibraryDependencyException;

/**
 * Provides a trait for external libraries that contain a single asset library.
 *
 * This trait should only be used by classes implementing
 * ExternalLibraryInterface.
 *
 * @see \Drupal\libraries\ExternalLibrary\Asset\AssetLibraryInterface
 * @see \Drupal\libraries\ExternalLibrary\ExternalLibraryInterface
 */
trait SingleAssetLibraryTrait {

  /**
   * Returns a core library array structure for this library.
   *
   * @return array
   *
   * @see \Drupal\libraries\ExternalLibrary\Asset\getAttachableAssetLibraries::getAttachableAssetLibraries()
   *
   * @throws \Drupal\libraries\ExternalLibrary\Exception\InvalidLibraryDependencyException
   *
   * @todo Document the return value.
   */
  public function getAttachableAssetLibraries() {
    return [$this->getId() => [
      'version' => $this->getVersion(),
      'css' => $this->getCssAssets(),
      'js' => $this->getJsAssets(),
      'dependencies' => $this->processDependencies($this->getDependencies()),
    ]];
  }

  /**
   * Processes a list of dependencies into a list of attachable library IDs.
   *
   * @param \Drupal\libraries\ExternalLibrary\ExternalLibraryInterface[] $dependencies
   *   An list of external libraries.
   *
   * @return string[]
   *   A list of attachable asset library IDs.
   *
   * @throws \Drupal\libraries\ExternalLibrary\Exception\InvalidLibraryDependencyException
   */
  protected function processDependencies(array $dependencies) {
    $attachable_dependency_ids = [];
    foreach ($dependencies as $dependency) {
      if (!$dependency instanceof AssetLibraryInterface) {
        /** @var \Drupal\libraries\ExternalLibrary\ExternalLibraryInterface $this */
        throw new InvalidLibraryDependencyException($this, $dependency);
      }

      foreach (array_keys($dependency->getAttachableAssetLibraries()) as $attachable_dependency_id) {
        // @todo It is not very elegant to hard-code the namespace here.
        $attachable_dependency_ids[] = 'libraries/' . $attachable_dependency_id;
      }
    }
    return $attachable_dependency_ids;
  }

  /**
   * Returns the ID of the library.
   *
   * @return string
   *   The library ID. This must be unique among all known libraries.
   *
   * @see \Drupal\libraries\ExternalLibrary\ExternalLibraryInterface::getId()
   */
  abstract public function getId();

  /**
   * Returns the currently installed version of the library.
   *
   * @return string
   *   The version string, for example 1.0, 2.1.4, or 3.0.0-alpha5.
   *
   * @see \Drupal\libraries\ExternalLibrary\ExternalLibraryInterface::getVersion()
   */
  abstract protected function getVersion();

  /**
   * Returns the libraries dependencies, if any.
   *
   * @return array
   *   An array of library IDs of libraries that the library depends on.
   *
   * @see \Drupal\libraries\ExternalLibrary\ExternalLibraryInterface::getDependencies()()
   */
  abstract protected function getDependencies();

  /**
   * Gets the CSS assets attached to this library.
   *
   * @return array
   *   An array of CSS assets of the library following the core library CSS
   *   structure. The keys of the array must be among the SMACSS categories
   *   'base', 'layout, 'component', 'state', and 'theme'. The value of each
   *   category is in turn an array where the keys are the file paths of the CSS
   *   files and values are CSS options.
   *
   * @see https://smacss.com/
   *
   * @todo Expand documentation.
   * @todo Consider adding separate methods for the CSS categories.
   */
  abstract protected function getCssAssets();

  /**
   * Gets the JavaScript assets attached to this library.
   *
   * @return array
   *   An array of JavaScript assets of the library. The keys of the array are
   *   the file paths of the JavaScript files and the values are JavaScript
   *   options.
   *
   * @todo Expand documentation.
   */
  abstract protected function getJsAssets();

}
