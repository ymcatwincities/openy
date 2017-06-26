<?php

/**
 * @file
 * Contains \Drupal\dropzonejs\UploadException.
 */

namespace Drupal\dropzonejs;

use Symfony\Component\HttpFoundation\JsonResponse;

class UploadException extends \Exception {

  /**
   * Error with input stream.
   */
  const INPUT_ERROR = 101;

  /**
   * Error with output stream.
   */
  const OUTPUT_ERROR = 102;

  /**
   * Error moving uploaded file.
   */
  const MOVE_ERROR = 103;

  /**
   * Error with destination folder.
   */
  const DESTINATION_FOLDER_ERROR = 104;

  /**
   * Error with temporary file name.
   */
  const FILENAME_ERROR = 105;

  /**
   * File upload resulted in error.
   */
  const FILE_UPLOAD_ERROR = 106;

  /**
   * Code to error message mapping.
   *
   * @param array $code
   */
  public $errorMessages = array(
    self::INPUT_ERROR => 'Failed to open input stream.',
    self::OUTPUT_ERROR => 'Failed to open output stream.',
    self::MOVE_ERROR => 'Failed to move uploaded file.',
    self::DESTINATION_FOLDER_ERROR => 'Failed to open temporary directory for write.',
    self::FILENAME_ERROR => 'Invalid temporary file name.',
    self::FILE_UPLOAD_ERROR => 'The file upload resulted in an error on php level. See http://php.net/manual/en/features.file-upload.errors.php',
  );

  /**
   * Constructs UploadException.
   *
   * @param int $code
   *   Error code.
   * @param string|null $message
   *   The error message. Defaults to null.
   */
  public function __construct($code, $message = NULL) {
    $this->code = $code;
    $this->message = isset($message) ? $message : $this->errorMessages[$this->code];
  }

  /**
   * Generates and returns JSON response object for the error.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   JSON response object.
   */
  public function getErrorResponse() {
    return new JsonResponse(
      array(
        'jsonrpc' => '2.0',
        'error' => $this->errorMessages[$this->code],
        'id' => 'id',
      ),
      500
    );
  }

}
