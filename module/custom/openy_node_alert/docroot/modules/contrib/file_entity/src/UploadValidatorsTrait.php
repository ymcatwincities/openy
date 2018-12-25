<?php

namespace Drupal\file_entity;

use Drupal\Component\Utility\Bytes;

/**
 * Trait for validating form uploads.
 */
trait UploadValidatorsTrait {

  /**
   * Retrieves the upload validators for a file or archive.
   *
   * @param array $options
   *   (optional) An array of options for file validation.
   *
   * @return array
   *   An array suitable for passing to file_save_upload() or for a managed_file
   *   or upload element's '#upload_validators' property.
   */
  public function getUploadValidators(array $options = array()) {
    // Set up file upload validators.
    $validators = array();

    // Validate file extensions. If there are no file extensions in $params and
    // there are no Media defaults, there is no file extension validation.
    if (!empty($options['file_extensions'])) {
      $validators['file_validate_extensions'] = array($options['file_extensions']);
    }

    // Cap the upload size according to the system or user defined limit.
    $max_filesize = file_upload_max_size();
    $user_max_filesize = Bytes::toInt(\Drupal::config('file_entity.settings')
      ->get('max_filesize'));

    // If the user defined a size limit, use the smaller of the two.
    if (!empty($user_max_filesize)) {
      $max_filesize = min($max_filesize, $user_max_filesize);
    }

    if (!empty($options['max_filesize']) && $options['max_filesize'] < $max_filesize) {
      $max_filesize = Bytes::toInt($options['max_filesize']);
    }

    // There is always a file size limit due to the PHP server limit.
    $validators['file_validate_size'] = array($max_filesize);

    // Add image validators.
    $options += array('min_resolution' => 0, 'max_resolution' => 0);
    if ($options['min_resolution'] || $options['max_resolution']) {
      $validators['file_validate_image_resolution'] = array(
        $options['max_resolution'],
        $options['min_resolution'],
      );
    }

    // Add other custom upload validators from options.
    if (!empty($options['upload_validators'])) {
      $validators += $options['upload_validators'];
    }

    return $validators;
  }

}
