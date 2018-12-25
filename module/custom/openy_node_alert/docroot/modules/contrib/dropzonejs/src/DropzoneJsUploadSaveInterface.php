<?php

namespace Drupal\dropzonejs;

use Drupal\Core\Session\AccountProxyInterface;
use Drupal\file\FileInterface;

/**
 * Provides an interface for classes that save DropzoneJs uploads.
 */
interface DropzoneJsUploadSaveInterface {

  /**
   * Creates a file entity form an uploaded file.
   *
   * Note: files being created using this method are flagged as temporary and
   * not saved yet.
   *
   * @param string $uri
   *   The path to the file we want to upload.
   * @param string $destination
   *   A string containing the URI that the file should be copied to. This must
   *   be a stream wrapper URI.
   * @param string $extensions
   *   A space separated list of valid extensions.
   * @param \Drupal\Core\Session\AccountProxyInterface $user
   *   The owner of the file.
   * @param array $validators
   *   (Optional) Associative array of callback functions used to validate the
   *   file. See file_validate() for more documentation. Note that we add
   *   file_validate_extensions and file_validate_name_length in this method
   *   already.
   *
   * @return \Drupal\file\FileInterface|bool
   *   The file entity of the newly uploaded file or false in case of a failure.
   *   The file isn't saved yet. That should be handled by the caller.
   */
  public function createFile($uri, $destination, $extensions, AccountProxyInterface $user, array $validators = []);

  /**
   * Validate the uploaded file.
   *
   * @param \Drupal\file\FileInterface $file
   *   The file entity object.
   * @param string $extensions
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
