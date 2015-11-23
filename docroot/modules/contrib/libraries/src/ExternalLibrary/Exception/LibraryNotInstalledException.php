<?php

/**
 * @file
 * Contains \Drupal\libraries\ExternalLibrary\Exception\LibraryDefinitionNotFoundException.
 */

namespace Drupal\libraries\ExternalLibrary\Exception;

use Drupal\libraries\ExternalLibrary\LocalLibraryInterface;
use Drupal\libraries\ExternalLibrary\Utility\LibraryAccessorTrait;
use Exception;

/**
 * Provides an exception for a library definition that cannot be found.
 */
class LibraryNotInstalledException extends \RuntimeException {

  use LibraryAccessorTrait;

  /**
   * Constructs a library exception.
   *
   * @param \Drupal\libraries\ExternalLibrary\LocalLibraryInterface $library
   *   The library that is not installed.
   * @param string $message
   *   (optional) The exception message.
   * @param int $code
   *   (optional) The error code.
   * @param \Exception $previous
   *   (optional) The previous exception.
   */
  public function __construct(
    LocalLibraryInterface $library,
    $message = '',
    $code = 0,
    \Exception $previous = NULL
  ) {
    $this->library = $library;
    $message = $message ?: "The library '{$this->library->getId()}' is not installed.";
    parent::__construct($message, $code, $previous);
  }

}
