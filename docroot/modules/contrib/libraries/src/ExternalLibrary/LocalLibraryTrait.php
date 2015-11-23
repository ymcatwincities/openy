<?php

/**
 * @file
 * Contains \Drupal\libraries\ExternalLibrary\LocalLibraryTrait.
 */

namespace Drupal\libraries\ExternalLibrary;

use Drupal\libraries\ExternalLibrary\Exception\LibraryNotInstalledException;

/**
 * Provides a trait for local libraries utilizing a stream wrapper.
 *
 * It assumes that the library files can be accessed using a specified stream
 * wrapper and that the first component of the file URIs are the library IDs.
 * Thus, file URIs are of the form:
 * stream-wrapper-scheme://library-id/path/to/file/within/the/library/filename
 *
 * This trait should only be used by classes implementing LocalLibraryInterface.
 *
 * @see \Drupal\libraries\ExternalLibrary\LocalLibraryInterface
 */
trait LocalLibraryTrait {

  /**
   * The file system helper.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystemHelper;

  /**
   * Checks whether the library is installed.
   *
   * @return bool
   *   TRUE if the library is installed; FALSE otherwise;
   */
  public function isInstalled() {
    return is_dir($this->getLibraryPath()) && is_readable($this->getLibraryPath());
  }

  /**
   * Gets the path to the library.
   *
   * @return string
   *   The path to the library.
   *
   * @throws \Drupal\libraries\ExternalLibrary\Exception\LibraryNotInstalledException
   */
  public function getLibraryPath() {
    // @todo Validate that the library is installed without causing infinite
    //   recursion.

    return $this->fileSystemHelper->realpath($this->getUri());
  }

  /**
   * Gets the URI of the library.
   *
   * @return string
   *   The URI of the library.
   */
  protected function getUri() {
    /** @var \Drupal\libraries\ExternalLibrary\LocalLibraryInterface|static $this */
    return $this->getScheme() . '://' . $this->getId();
  }

  /**
   * Gets the URI scheme that is used for this type of library.
   *
   * @return string
   *   The URI scheme for this library.
   */
  abstract protected function getScheme();

}
