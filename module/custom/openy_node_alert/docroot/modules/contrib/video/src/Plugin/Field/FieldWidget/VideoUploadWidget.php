<?php

/**
 * @file
 * Contains \Drupal\video\Plugin\Field\FieldWidget\VideoUploadWidget.
 */

namespace Drupal\video\Plugin\Field\FieldWidget;

use Drupal\Component\Utility\Bytes;
use Drupal\Component\Render\PlainTextOutput;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Form\FormStateInterface;
use Drupal\file\Entity\File;
use Drupal\file\Plugin\Field\FieldWidget\FileWidget;
use Drupal\Core\StreamWrapper\StreamWrapperInterface;

/**
 * Plugin implementation of the 'video_upload' widget.
 *
 * @FieldWidget(
 *   id = "video_upload",
 *   label = @Translation("Video Upload"),
 *   field_types = {
 *     "video"
 *   }
 * )
 */
class VideoUploadWidget extends FileWidget {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    $settings = array(
      'file_extensions' => 'mp4 ogv webm',
      'file_directory' => 'videos/[date:custom:Y]-[date:custom:m]',
      'max_filesize' => '',
      'uri_scheme' => 'public'
    ) + parent::defaultSettings();
    return $settings;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element = parent::settingsForm($form, $form_state);
    $settings = $this->getSettings();

    $element['file_directory'] = array(
      '#type' => 'textfield',
      '#title' => t('File directory'),
      '#default_value' => $settings['file_directory'],
      '#description' => t('Optional subdirectory within the upload destination where files will be stored. Do not include preceding or trailing slashes.'),
      '#element_validate' => array(array(get_class($this), 'validateDirectory')),
      '#weight' => 3,
    );

    // Make the extension list a little more human-friendly by comma-separation.
    $extensions = str_replace(' ', ', ', $settings['file_extensions']);
    $element['file_extensions'] = array(
      '#type' => 'textfield',
      '#title' => t('Allowed file extensions'),
      '#default_value' => $extensions,
      '#description' => t('Separate extensions with a space or comma and do not include the leading dot.'),
      '#element_validate' => array(array(get_class($this), 'validateExtensions')),
      '#weight' => 1,
      '#maxlength' => 256,
      // By making this field required, we prevent a potential security issue
      // that would allow files of any type to be uploaded.
      '#required' => TRUE,
    );

    $element['max_filesize'] = array(
      '#type' => 'textfield',
      '#title' => t('Maximum upload size'),
      '#default_value' => $settings['max_filesize'],
      '#description' => t('Enter a value like "512" (bytes), "80 KB" (kilobytes) or "50 MB" (megabytes) in order to restrict the allowed file size. If left empty the file sizes will be limited only by PHP\'s maximum post and file upload sizes (current limit <strong>%limit</strong>).', array('%limit' => format_size(file_upload_max_size()))),
      '#size' => 10,
      '#element_validate' => array(array(get_class($this), 'validateMaxFilesize')),
      '#weight' => 5,
    );
    
    $scheme_options = \Drupal::service('stream_wrapper_manager')->getNames(StreamWrapperInterface::WRITE_VISIBLE);
    $element['uri_scheme'] = array(
      '#type' => 'radios',
      '#title' => t('Upload destination'),
      '#options' => $scheme_options,
      '#default_value' => $this->getSetting('uri_scheme'),
      '#description' => t('Select where the final files should be stored. Private file storage has significantly more overhead than public files, but allows restricted access to files within this field.'),
      '#weight' => 6,
    );
    return $element;
  }

  /**
   * Form API callback
   *
   * Removes slashes from the beginning and end of the destination value and
   * ensures that the file directory path is not included at the beginning of the
   * value.
   *
   * This function is assigned as an #element_validate callback in
   * settingsForm().
   */
  public static function validateDirectory($element, FormStateInterface $form_state) {
    // Strip slashes from the beginning and end of $element['file_directory'].
    $value = trim($element['#value'], '\\/');
    $form_state->setValueForElement($element, $value);
  }

  /**
   * Form API callback.
   *
   * This function is assigned as an #element_validate callback in
   * settingsForm().
   *
   * This doubles as a convenience clean-up function and a validation routine.
   * Commas are allowed by the end-user, but ultimately the value will be stored
   * as a space-separated list for compatibility with file_validate_extensions().
   */
  public static function validateExtensions($element, FormStateInterface $form_state) {
    if (!empty($element['#value'])) {
      $extensions = preg_replace('/([, ]+\.?)/', ' ', trim(strtolower($element['#value'])));
      $extensions = array_filter(explode(' ', $extensions));
      $extensions = implode(' ', array_unique($extensions));
      if (!preg_match('/^([a-z0-9]+([.][a-z0-9])* ?)+$/', $extensions)) {
        $form_state->setError($element, t('The list of allowed extensions is not valid, be sure to exclude leading dots and to separate extensions with a comma or space.'));
      }
      else {
        $form_state->setValueForElement($element, $extensions);
      }
    }
  }

  /**
   * Form API callback.
   *
   * Ensures that a size has been entered and that it can be parsed by
   * \Drupal\Component\Utility\Bytes::toInt().
   *
   * This function is assigned as an #element_validate callback in
   * settingsForm().
   */
  public static function validateMaxFilesize($element, FormStateInterface $form_state) {
    if (!empty($element['#value']) && (Bytes::toInt($element['#value']) == 0)) {
      $form_state->setError($element, t('The option must contain a valid value. You may either leave the text field empty or enter a string like "512" (bytes), "80 KB" (kilobytes) or "50 MB" (megabytes).'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = array();
    $summary[] = t('Progress indicator: @progress_indicator<br/>Extensions : @file_extensions<br/>File directory : @file_directory<br/>@max_filesize', 
    array(
      '@progress_indicator' => $this->getSetting('progress_indicator'),
      '@file_extensions' => $this->getSetting('file_extensions'),
      '@file_directory' => $this->getSetting('uri_scheme') . '://' . $this->getSetting('file_directory'),
      '@max_filesize' => ($this->getSetting('max_filesize')) ? 'Max filesize: ' . $this->getSetting('max_filesize') : '',
    ));
    return $summary;
  }

  /**
   * Overrides \Drupal\file\Plugin\Field\FieldWidget\FileWidget::formMultipleElements().
   *
   * Special handling for draggable multiple widgets and 'add more' button.
   */
  protected function formMultipleElements(FieldItemListInterface $items, array &$form, FormStateInterface $form_state) {
    $elements = parent::formMultipleElements($items, $form, $form_state);

    $cardinality = $this->fieldDefinition->getFieldStorageDefinition()->getCardinality();
    $file_upload_help = array(
      '#theme' => 'file_upload_help',
      '#description' => '',
      '#upload_validators' => $elements[0]['#upload_validators'],
      '#cardinality' => $cardinality,
    );
    if ($cardinality == 1) {
      // If there's only one field, return it as delta 0.
      if (empty($elements[0]['#default_value']['fids'])) {
        $file_upload_help['#description'] = $this->fieldDefinition->getDescription();
        $elements[0]['#description'] = \Drupal::service('renderer')->renderPlain($file_upload_help);
      }
    }
    else {
      $elements['#file_upload_description'] = $file_upload_help;
    }

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);

    // If not using custom extension validation, ensure this is a video.
    $element['#upload_location'] = $this->getUploadLocation();
    $element['#upload_validators'] = $this->getUploadValidators();

    return $element;
  }

  /**
   * Determines the URI for a video field.
   *
   * @param array $data
   *   An array of token objects to pass to token_replace().
   *
   * @return string
   *   An unsanitized file directory URI with tokens replaced. The result of
   *   the token replacement is then converted to plain text and returned.
   *
   * @see token_replace()
   */
  public function getUploadLocation($data = array()) {
    return static::doGetUploadLocation($this->getSettings(), $data);
  }

  /**
   * Determines the URI for a video field.
   *
   * @param array $settings
   *   The array of field settings.
   * @param array $data
   *   An array of token objects to pass to token_replace().
   *
   * @return string
   *   An unsanitized file directory URI with tokens replaced. The result of
   *   the token replacement is then converted to plain text and returned.
   */
  protected static function doGetUploadLocation(array $settings, $data = []) {
    $destination = trim($settings['file_directory'], '/');

    // Replace tokens. As the tokens might contain HTML we convert it to plain
    // text.
    $destination = PlainTextOutput::renderFromHtml(\Drupal::token()->replace($destination, $data));
    return $settings['uri_scheme'] . '://' . $destination;
  }

  /**
   * Retrieves the upload validators for a video field.
   *
   * @return array
   *   An array suitable for passing to file_save_upload() or the file field
   *   element's '#upload_validators' property.
   */
  public function getUploadValidators() {
    $validators = array();
    $settings = $this->getSettings();

    // Cap the upload size according to the PHP limit.
    $max_filesize = Bytes::toInt(file_upload_max_size());
    if (!empty($settings['max_filesize'])) {
      $max_filesize = min($max_filesize, Bytes::toInt($settings['max_filesize']));
    }

    // There is always a file size limit due to the PHP server limit.
    $validators['file_validate_size'] = array($max_filesize);

    // Add the extension check if necessary.
    if (!empty($settings['file_extensions'])) {
      $validators['file_validate_extensions'] = array($settings['file_extensions']);
    }

    return $validators;
  }

  /**
   * Form API callback: Processes a video_upload field element.
   *
   * This method is assigned as a #process callback in formElement() method.
   */
  public static function process($element, FormStateInterface $form_state, $form) {
    return parent::process($element, $form_state, $form);
  }
}
