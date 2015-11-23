<?php

/**
 * @file
 * Contains \Drupal\libraries\StreamWrapper\PhpFileLibraryStream.
 */

namespace Drupal\libraries\StreamWrapper;

use Drupal\Core\StreamWrapper\LocalStream;
use Drupal\Core\StreamWrapper\StreamWrapperInterface;

/**
 * Provides a stream wrapper for PHP file libraries.
 *
 * Can be used with the 'php-file-library://' scheme, for example
 * 'php-file-library://guzzle/src/functions_include.php'.
 */
class PhpLibraryFilesStream extends LocalStream {

  use LocalHiddenStreamTrait;
  use PrivateStreamTrait;

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return t('PHP library files');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return t('Provides access to PHP library files.');
  }

  /**
   * {@inheritdoc}
   */
  public function getDirectoryPath() {
    return 'sites/all/libraries';
  }

}
