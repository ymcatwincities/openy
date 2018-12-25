<?php

namespace Drupal\yptf_kronos\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides form for upload MindBody CSV file.
 */
class KronosMBCSVUpload extends FormBase {

  /**
   * Directory in the filesystem.
   */
  const DIR = 'mb_kronos_reports';

  /**
   * MindBody file format.
   */
  const MINDBODY_CSV_FILE_FORMAT = 'MB_%s--%s.csv';

  /**
   * Output MindBody file date format .
   */
  const DATE_OUTPUT_FORMAT = 'Y-m-d';

  /**
   * Input MindBody file date format.
   */
  const DATE_INPUT_FORMAT = 'n/j/Y';

  /**
   * URI for destination directory.
   *
   * @var string
   */
  protected $destinationUri;

  /**
   * Path for destination directory.
   *
   * @var string
   */
  protected $destinationDir;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'yptf_kronos_mb_csv';
  }

  /**
   * KronosMBCSVUpload constructor.
   */
  public function __construct() {
    // Make sure, directory for uploads exists.
    $this->destinationUri = sprintf('public://%s/', self::DIR);
    $this->destinationDir = \Drupal::service('file_system')->realpath($this->destinationUri);
    file_prepare_directory($this->destinationDir, FILE_MODIFY_PERMISSIONS);
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['description'] = [
      '#markup' => 'Please, use this form for uploading exported MindBody CSV files. The system automatically detects the dates from the file header and creates appropriate file in the file system.',
    ];

    $validators = [
      'file_validate_extensions' => ['csv'],
    ];

    $form['file'] = array(
      '#type' => 'managed_file',
      '#title' => t('Exported MindBody CSV file'),
      '#size' => 20,
      '#description' => t('Only CSV files are accepted.'),
      '#upload_validators' => $validators,
      '#upload_location' => $this->destinationUri,
      '#required' => TRUE,
    );

    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Save'),
      '#button_type' => 'primary',
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $file = $form_state->getValue(['file']);
    if (empty($file) || !isset($file[0]) || empty($file[0])) {
      $form_state->setErrorByName('file', 'Failed to upload the file. Please, try again.');
      return;
    }

    /** @var \Drupal\file\FileInterface $file */
    $file = \Drupal::entityTypeManager()->getStorage('file')->load($file[0]);
    if (!$file) {
      $form_state->setErrorByName('file', 'Failed to read upload the file. Please, try again.');
      return;
    }

    $filePath = \Drupal::service('file_system')->realpath($file->getFileUri());
    $contents = file_get_contents($filePath);
    $lines = explode(PHP_EOL, $contents);

    // Try to get dates from the first line.
    preg_match("/\[Start date\]=(\d+\/\d+\/\d+),\[End date\]=(\d+\/\d+\/\d+)/", $lines[0], $test);
    if (
      !isset($test[1]) || empty($test[1]) ||
      !isset($test[2]) || empty($test[2])
    ) {
      $form_state->setErrorByName('file', 'Failed to get dates from file. Please, check your file.');
      return;
    }

    $form_state->setValue('dates', [$test[1], $test[2]]);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Move the file to appropriate place.
    $file = $form_state->getValue(['file']);
    /** @var \Drupal\file\FileInterface $file */
    $file = \Drupal::entityTypeManager()->getStorage('file')->load($file[0]);

    // Format destination name.
    $dates = $form_state->getValue('dates');
    $startDate = \DateTime::createFromFormat(self::DATE_INPUT_FORMAT, $dates[0]);
    $endDate = \DateTime::createFromFormat(self::DATE_INPUT_FORMAT, $dates[1]);
    $destinationName = sprintf(
      self::MINDBODY_CSV_FILE_FORMAT,
      $startDate->format(self::DATE_OUTPUT_FORMAT),
      $endDate->format(self::DATE_OUTPUT_FORMAT)
    );

    $destination = sprintf('%s/%s', $this->destinationUri, $destinationName);

    $file = file_move($file, $destination, FILE_EXISTS_REPLACE);
    $file->setPermanent();
    $file->save();

    \Drupal::logger('yptf_kronos')->info('MindBody file has been uploaded: %file', ['%file' => $destinationName]);
    drupal_set_message(sprintf('MindBody file "%s" has been uploaded.', $destinationName));
  }

}
