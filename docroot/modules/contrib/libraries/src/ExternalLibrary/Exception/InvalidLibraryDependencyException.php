<?php

/**
 * @file
 * Contains \Drupal\libraries\ExternalLibrary\Exception\InvalidLibraryDependencyException.
 */

namespace Drupal\libraries\ExternalLibrary\Exception;
use Drupal\libraries\ExternalLibrary\DependencyAccessorTrait;
use Drupal\libraries\ExternalLibrary\ExternalLibraryInterface;
use Drupal\libraries\ExternalLibrary\LibraryAccessorTrait;

/**
 *
 */
class InvalidLibraryDependencyException extends \UnexpectedValueException {

  use LibraryAccessorTrait;
  use DependencyAccessorTrait;

  /**
   * Constructs a library exception.
   *
   * @param \Drupal\libraries\ExternalLibrary\ExternalLibraryInterface $library
   *   The library with the invalid dependency.
   * @param \Drupal\libraries\ExternalLibrary\ExternalLibraryInterface $dependency
   *   The dependency.
   * @param string $message
   *   (optional) The exception message.
   * @param int $code
   *   (optional) The error code.
   * @param \Exception $previous
   *   (optional) The previous exception.
   */
  public function __construct(
    ExternalLibraryInterface $library,
    ExternalLibraryInterface $dependency,
    $message = '',
    $code = 0,
    \Exception $previous = NULL
  ) {
    $this->library = $library;
    $this->dependency = $dependency;
    $message = $message ?: "The library '{$this->library->getId()}' cannot depend on the library '{$this->dependency->getId()}'.";
    parent::__construct($message, $code, $previous);
  }

}

