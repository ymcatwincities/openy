<?php

/**
 * @file
 * Contains \Drupal\dropzonejs\DropzoneJsUploadSaveInterface.
 */

namespace Drupal\dropzonejs;

use Drupal\Core\Session\AccountProxyInterface;
use Drupal\file\FileInterface;

/**
 * Provides an interface for classes that save DropzoneJs uploads.
 */
interface DropzoneJsUploadSaveInterface {

  /**
   * Save a uploaded file.
   *
   * Note: files beeing saved using this method are still flagged as temporary.
   *
   * @param string $uri
   *   The path to the file we want to upload.
   * @param string $destination
   *   A string containing the URI that the file should be copied to. This must
   *   be a stream wrapper URI.
   * @param string $extensions
   *   A space separated list of valid extensions.
   * @param \Drupal\Core\Session\AccountProxyInterfac $user
   *   The owner of the file.
   * @param array $validators
   *   An optional, associative array of callback functions used to validate the
   *   file. See file_validate() for more documentation. Note that we add
   *   file_validate_extensions and file_validate_name_length in this method
   *   already.
   *
   * @return \Drupal\file\FileInterface|bool
   *   The saved file entity of the newly created file entity or false if
   *   saving failed.
   */
  public function saveFile($uri, $destination, $extensions, AccountProxyInterface $user, $validators = []);

  /**
   * Prepare a file entity from uri.
   *
   * @param string $uri
   *   File's uri.
   * @param \Drupal\Core\Session\AccountProxyInterface $user
   *   The owner of the file.
   *
   * @return \Drupal\file\FileInterface
   *   A new entity file entity object, not saved yet.
   */
  public function fileEntityFromUri($uri, AccountProxyInterface $user);

  /**
   * Validate the uploaded file.
   *
   * @param \Drupal\file\FileInterface $file
   *   The file entity object.
   * @param array $extensions
   *   A space separated string of valid extensions.
   * @param array $additional_validators
   *   An optional, associative array of callback functions used to validate the
   *   file. See file_validate() for more documentation. Note that we add
   *   file_validate_extensions and file_validate_name_length in this method
   *   already.
   *
   * @return array
   *   An array containing validation error messages.
   */
  public function validateFile(FileInterface $file, $extensions, array $additional_validators = []);
}
