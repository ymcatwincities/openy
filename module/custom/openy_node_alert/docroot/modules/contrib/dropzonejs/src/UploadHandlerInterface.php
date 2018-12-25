<?php

namespace Drupal\dropzonejs;

use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Interface UploadHandlerInterface.
 */
interface UploadHandlerInterface {

  /**
   * Reads, checks and return filename of a file being uploaded.
   *
   * @param \Symfony\Component\HttpFoundation\File\UploadedFile $file
   *   An instance of UploadedFile.
   *
   * @return string
   *   The sanitized filename.
   *
   * @throws \Drupal\dropzonejs\UploadException
   */
  public function getFilename(UploadedFile $file);

  /**
   * Handles an uploaded file.
   *
   * @param \Symfony\Component\HttpFoundation\File\UploadedFile $file
   *   The uploaded file.
   *
   * @return string
   *   URI of the uploaded file.
   *
   * @throws \Drupal\dropzonejs\UploadException
   */
  public function handleUpload(UploadedFile $file);

}
