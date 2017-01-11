<?php

namespace Drupal\dropzonejs;

use Drupal\Component\Transliteration\TransliterationInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Handles files uploaded by Dropzone.
 *
 * The uploaded file will be stored in the configured tmp folder and will be
 * added a tmp extension. Further filename processing will be done in
 * Drupal\dropzonejs\Element::valueCallback. This means that the final
 * filename will be provided only after that callback.
 */
class UploadHandler implements UploadHandlerInterface {

  /**
   * The current request.
   *
   * @var \Symfony\Component\HttpFoundation\Request $request
   *   The HTTP request object.
   */
  protected $request;

  /**
   * Stores temporary folder URI.
   *
   * This is configurable via the configuration variable. It was added for HA
   * environments where temporary location may need to be a shared across all
   * servers.
   *
   * @var string
   */
  protected $temporaryUploadLocation;

  /**
   * Transliteration service.
   *
   * @var \Drupal\Core\Transliteration\PhpTransliteration
   */
  protected $transliteration;

  /**
   * Constructs dropzone upload controller route controller.
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config
   *   Config factory.
   * @param \Drupal\Component\Transliteration\TransliterationInterface $transliteration
   *   Transliteration service.
   */
  public function __construct(RequestStack $request_stack, ConfigFactoryInterface $config, TransliterationInterface $transliteration) {
    $this->request = $request_stack->getCurrentRequest();
    $tmp_override = $config->get('dropzonejs.settings')->get('tmp_dir');
    $this->temporaryUploadLocation = $tmp_override ?: $config->get('system.file')->get('path.temporary');
    $this->transliteration = $transliteration;
  }

  /**
   * Prepares temporary destination folder for uploaded files.
   *
   * @throws \Drupal\dropzonejs\UploadException
   */
  protected function prepareTemporaryUploadDestination() {
    $writable = file_prepare_directory($this->temporaryUploadLocation, FILE_CREATE_DIRECTORY);
    if (!$writable) {
      throw new UploadException(UploadException::DESTINATION_FOLDER_ERROR);
    }

    // Try to make sure this is private via htaccess.
    file_save_htaccess($this->temporaryUploadLocation, TRUE);
  }

  /**
   * {@inheritdoc}
   */
  public function getFilename(UploadedFile $file) {
    $original_name = $file->getClientOriginalName();

    // There should be a filename and it should not contain a semicolon,
    // which we use to separate filenames.
    if (!isset($original_name)) {
      throw new UploadException(UploadException::FILENAME_ERROR);
    }

    // Transliterate.
    $processed_filename = $this->transliteration->transliterate($original_name);

    // For security reasons append the txt extension. It will be removed in
    // Drupal\dropzonejs\Element::valueCallback when we will know the valid
    // extension and we will be able to properly sanitize the filename.
    $processed_filename = $processed_filename . '.txt';

    return $processed_filename;
  }

  /**
   * {@inheritdoc}
   */
  public function handleUpload(UploadedFile $file) {
    $this->prepareTemporaryUploadDestination();

    $error = $file->getError();
    if ($error != UPLOAD_ERR_OK) {
      // Check for file upload errors and return FALSE for this file if a lower
      // level system error occurred. For a complete list of errors:
      // See http://php.net/manual/features.file-upload.errors.php.
      switch ($error) {
        case UPLOAD_ERR_INI_SIZE:
        case UPLOAD_ERR_FORM_SIZE:
          $message = t('The file could not be saved because it exceeds the maximum allowed size for uploads.');
          continue;

        case UPLOAD_ERR_PARTIAL:
        case UPLOAD_ERR_NO_FILE:
          $message = t('The file could not be saved because the upload did not complete.');
          continue;

        // Unknown error.
        default:
          $message = t('The file could not be saved. An unknown error has occurred.');
          continue;
      }

      throw new UploadException(UploadException::FILE_UPLOAD_ERROR, $message);
    }

    // Open temp file.
    $tmp = "{$this->temporaryUploadLocation}/{$this->getFilename($file)}";
    if (!($out = fopen($tmp, $this->request->request->get('chunk', 0) ? 'ab' : 'wb'))) {
      throw new UploadException(UploadException::OUTPUT_ERROR);
    }

    // Read binary input stream.
    $input_uri = $file->getFileInfo()->getRealPath();
    if (!($in = fopen($input_uri, 'rb'))) {
      throw new UploadException(UploadException::INPUT_ERROR);
    }

    // Append input stream to temp file.
    while ($buff = fread($in, 4096)) {
      fwrite($out, $buff);
    }

    // Be nice and keep everything nice and clean. Initial uploaded files are
    // automatically removed by PHP at the end of the request so we don't need
    // to do that.
    // @todo when implementing multipart don't forget to drupal_unlink.
    fclose($in);
    fclose($out);

    return $tmp;
  }

}
