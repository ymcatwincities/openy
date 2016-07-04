<?php

/**
 * Contains \Drupal\dropzonejs_eb_widget\Plugin\EntityBrowser\Widget\DropzoneJsEbWidget.
 */

namespace Drupal\dropzonejs_eb_widget\Plugin\EntityBrowser\Widget;

use Drupal\Component\Utility\Bytes;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\dropzonejs\DropzoneJsUploadSaveInterface;
use Drupal\entity_browser\WidgetBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Provides an Entity Browser widget that uploads new files.
 *
 * @EntityBrowserWidget(
 *   id = "dropzonejs",
 *   label = @Translation("DropzoneJS"),
 *   description = @Translation("Adds DropzoneJS upload integration.")
 * )
 */
class DropzoneJsEbWidget extends WidgetBase {

  /**
   * DropzoneJS module upload save service.
   *
   * @var \Drupal\dropzonejs\DropzoneJsUploadSaveInterface
   */
  protected $dropzoneJsUploadSave;

  /**
   * Current user service.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * Constructs widget plugin.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   Event dispatcher service.
   * @param \Drupal\dropzonejs\DropzoneJsUploadSaveInterface $dropzonejs_upload_save
   *   The upload saving dropzonejs service.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The current user service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EventDispatcherInterface $event_dispatcher, EntityManagerInterface $entity_manager, DropzoneJsUploadSaveInterface $dropzonejs_upload_save, AccountProxyInterface $current_user) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $event_dispatcher, $entity_manager);
    $this->dropzoneJsUploadSave = $dropzonejs_upload_save;
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('event_dispatcher'),
      $container->get('entity.manager'),
      $container->get('dropzonejs.upload_save'),
      $container->get('current_user')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'upload_location' => 'public://[date:custom:Y]-[date:custom:m]',
      'dropzone_description' => t('Drop files here to upload them'),
      'max_filesize' => file_upload_max_size() / pow(Bytes::KILOBYTE, 2) . 'M',
      'extensions' => 'jpg jpeg gif png txt doc xls pdf ppt pps odt ods odp',
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function getForm(array &$original_form, FormStateInterface $form_state, array $aditional_widget_parameters) {
    $config = $this->getConfiguration();
    $form['upload'] = [
      '#title' => t('File upload'),
      '#type' => 'dropzonejs',
      '#required' => TRUE,
      '#dropzone_description' => $config['settings']['dropzone_description'],
      '#max_filesize' => $config['settings']['max_filesize'],
      '#extensions' => $config['settings']['extensions'],
    ];

    // Disable the submit button until the upload sucesfully completed.
    $form['#attached']['library'][] = 'dropzonejs_eb_widget/common';
    $original_form['#attributes']['class'][] = 'dropzonejs-disable-submit';

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validate(array &$form, FormStateInterface $form_state) {
    $upload = $form_state->getValue(['upload'], []);
    $trigger = $form_state->getTriggeringElement();
    $config = $this->getConfiguration();

    // Validation configuration.
    $extensions = $config['settings']['extensions'];
    $max_filesize = $config['settings']['max_filesize'];

    // Validate if we are in fact uploading a files and all of them have the
    // right extensions. Although DropzoneJS should not even upload those files
    // it's still better not to rely only on client side validation.
    if ($trigger['#value'] == 'Select') {
      if (!empty($upload['uploaded_files'])) {
        $errors = [];
        // @todo Check per user size allowance.
        $additional_validators = ['file_validate_size' => [Bytes::toInt($max_filesize), 0]];

        foreach ($upload['uploaded_files'] as $file) {
          $file = $this->dropzoneJsUploadSave->fileEntityFromUri($file['path'], $this->currentUser);
          $errors += $this->dropzoneJsUploadSave->validateFile($file, $extensions, $additional_validators);
        }

        if (!empty($errors)) {
          // @todo Output the actual errors from validateFile.
          $form_state->setError($form['widget']['upload'], t('Some files that you are trying to upload did not pass validation. Requirements are: max file %size, allowed extensions are %extensions', ['%size' => $max_filesize, '%extensions' => $extensions]));
        }
      }
      else {
        $form_state->setError($form['widget']['upload'], t('At least one valid file should be uploaded.'));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submit(array &$element, array &$form, FormStateInterface $form_state) {
    $files = [];
    $upload = $form_state->getValue('upload');
    $config = $this->getConfiguration();
    $user = \Drupal::currentUser();

    foreach ($upload['uploaded_files'] as $uploaded_file) {
      $file = $this->dropzoneJsUploadSave->saveFile($uploaded_file['path'], $config['settings']['upload_location'], $config['settings']['extensions'], $user);

      if ($file) {
        $file->setPermanent();
        $file->save();
        $files[] = $file;
      }
    }

    if (!empty(array_filter($files))) {
      $this->selectEntities($files, $form_state);
      $this->clearFormValues($element, $form_state);
    }
  }

  /**
   * Clear values from upload form element.
   *
   * @param array $element
   *   Upload form element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state object.
   */
  protected function clearFormValues(array &$element, FormStateInterface $form_state) {
    // We propagated entities to the other parts of the system. We can now
    // remove them from our values.
    $form_state->setValueForElement($element['upload']['uploaded_files'], '');
    NestedArray::setValue($form_state->getUserInput(), $element['upload']['uploaded_files']['#parents'], '');
  }

  /**
   * Validate extension.
   *
   * Because while validating we don't have a file object yet, we can't use
   * file_validate_extensions directly. That's why we make a copy of that
   * function here and switch the file argument with filename argument.
   *
   * @param string $filename
   *   The filename we want to test.
   * @param string $extensions
   *   A space separated list of extensions.
   *
   * @return bool
   *   True if the file's extension is a valid one. False otherwise.
   */
  protected function validateExtension($filename, $extensions) {
    $regex = '/\.(' . preg_replace('/ +/', '|', preg_quote($extensions)) . ')$/i';
    if (!preg_match($regex, $filename)) {
      return FALSE;
    }
    return TRUE;
  }

}
