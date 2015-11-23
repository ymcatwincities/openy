<?php

/**
 * @file
 * Contains \Drupal\Tests\libraries\Kernel\ExternalLibrary\PhpFile\TestPhpLibraryFilesStream.
 */

namespace Drupal\Tests\libraries\Kernel\ExternalLibrary\PhpFile;

use Drupal\libraries\StreamWrapper\PhpLibraryFilesStream;

/**
 *
 */
class TestPhpLibraryFilesStream extends PhpLibraryFilesStream {

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */

  /**
   * Constructs a test PHP library files stream.
   */
  public function __construct() {
    $this->moduleHandler = \Drupal::moduleHandler();
  }

  /**
   * {@inheritdoc}
   */
  public function getDirectoryPath() {
    return $this->moduleHandler->getModule('libraries')->getPath() . '/tests/libraries';
  }


}

