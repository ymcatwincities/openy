<?php

namespace Drupal\dropzonejs_eb_widget\Plugin\EntityBrowser\Widget;

use Drupal\Component\Utility\Bytes;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Utility\Token;
use Drupal\dropzonejs\DropzoneJsUploadSaveInterface;
use Drupal\entity_browser\WidgetBase;
use Drupal\entity_browser\WidgetValidationManager;
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
   * The token service.
   *
   * @var \Drupal\Core\Utility\Token
   */
  protected $token;

  /**
   * Uploaded files.
   *
   * @var \Drupal\file\FileInterface[]
   */
  protected $files;

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
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\entity_browser\WidgetValidationManager $validation_manager
   *   The Widget Validation Manager service.
   * @param \Drupal\dropzonejs\DropzoneJsUploadSaveInterface $dropzonejs_upload_save
   *   The upload saving dropzonejs service.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The current user service.
   * @param \Drupal\Core\Utility\Token $token
   *   The token service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EventDispatcherInterface $event_dispatcher, EntityTypeManagerInterface $entity_type_manager, WidgetValidationManager $validation_manager, DropzoneJsUploadSaveInterface $dropzonejs_upload_save, AccountProxyInterface $current_user, Token $token) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $event_dispatcher, $entity_type_manager, $validation_manager);
    $this->dropzoneJsUploadSave = $dropzonejs_upload_save;
    $this->currentUser = $current_user;
    $this->token = $token;
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
      $container->get('entity_type.manager'),
      $container->get('plugin.manager.entity_browser.widget_validation'),
      $container->get('dropzonejs.upload_save'),
      $container->get('current_user'),
      $container->get('token')
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
  public function getForm(array &$original_form, FormStateInterface $form_state, array $additional_widget_parameters) {
    $form = parent::getForm($original_form, $form_state, $additional_widget_parameters);

    $cardinality = 0;
    $validators = $form_state->get(['entity_browser', 'validators']);
    if (!empty($validators['cardinality']['cardinality'])) {
      $cardinality = $validators['cardinality']['cardinality'];
    }
    $config = $this->getConfiguration();
    $form['upload'] = [
      '#title' => t('File upload'),
      '#type' => 'dropzonejs',
      '#required' => TRUE,
      '#dropzone_description' => $config['settings']['dropzone_description'],
      '#max_filesize' => $config['settings']['max_filesize'],
      '#extensions' => $config['settings']['extensions'],
      '#max_files' => ($cardinality > 0) ? $cardinality : 0,
    ];

    $form['#attached']['library'][] = 'dropzonejs/widget';
    // Disable the submit button until the upload sucesfully completed.
    $form['#attached']['library'][] = 'dropzonejs_eb_widget/common';
    $original_form['#attributes']['class'][] = 'dropzonejs-disable-submit';

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function prepareEntities(array $form, FormStateInterface $form_state) {
    return $this->getFiles($form, $form_state);
  }

  /**
   * Gets uploaded files.
   *
   * We implement this to allow child classes to operate on different entity
   * type while still having access to the files in the validate callback here.
   *
   * @param array $form
   *   Form structure.
   * @param FormStateInterface $form_state
   *   Form state object.
   *
   * @return \Drupal\file\FileInterface[]
   *   Array of uploaded files.
   */
  protected function getFiles(array $form, FormStateInterface $form_state) {
    $config = $this->getConfiguration();
    $additional_validators = ['file_validate_size' => [Bytes::toInt($config['settings']['max_filesize']), 0]];

    if (!$this->files) {
      $this->files = [];

      $files = $form_state->get(['dropzonejs', 'files']);
      if ($files) {
        $this->files = $files;
        return $files;
      }

      // We do some casting because $form_state->getValue() might return NULL.
      foreach ((array) $form_state->getValue(['upload', 'uploaded_files'], []) as $file) {
        $entity = $this->dropzoneJsUploadSave->createFile(
          $file['path'],
          $this->getUploadLocation(),
          $config['settings']['extensions'],
          $this->currentUser,
          $additional_validators
        );
        $this->files[] = $entity;
      }
    }

    $form_state->set(['dropzonejs', 'files'], $this->files);
    return $this->files;
  }

  /**
   * Gets upload location.
   *
   * @return string
   *   Destination folder URI.
   */
  protected function getUploadLocation() {
    return $this->token->replace($this->configuration['upload_location']);
  }

  /**
   * {@inheritdoc}
   */
  public function validate(array &$form, FormStateInterface $form_state) {
    $trigger = $form_state->getTriggeringElement();

    // Validate if we are in fact uploading a files and all of them have the
    // right extensions. Although DropzoneJS should not even upload those files
    // it's still better not to rely only on client side validation.
    if ($trigger['#type'] == 'submit' && $trigger['#name'] == 'op') {
      $upload_location = $this->getUploadLocation();
      if (!file_prepare_directory($upload_location, FILE_CREATE_DIRECTORY | FILE_MODIFY_PERMISSIONS)) {
        $form_state->setError($form['widget']['upload'], t('Files could not be uploaded because the destination directory %destination is not configured correctly.', ['%destination' => $this->getConfiguration()['settings']['upload_location']]));
      }

      $files = $this->getFiles($form, $form_state);
      if (in_array(FALSE, $files)) {
        // @todo Output the actual errors from validateFile.
        $form_state->setError($form['widget']['upload'], t('Some files that you are trying to upload did not pass validation. Requirements are: max file %size, allowed extensions are %extensions', ['%size' => $this->getConfiguration()['settings']['max_filesize'], '%extensions' => $this->getConfiguration()['settings']['extensions']]));
      }

      if (empty($files)) {
        $form_state->setError($form['widget']['upload'], t('At least one valid file should be uploaded.'));
      }

      // If there weren't any errors set, run the normal validators.
      if (empty($form_state->getErrors())) {
        parent::validate($form, $form_state);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submit(array &$element, array &$form, FormStateInterface $form_state) {
    $files = [];
    foreach ($this->prepareEntities($form, $form_state) as $file) {
      $file->setPermanent();
      $file->save();
      $files[] = $file;
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

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $configuration = $this->configuration;

    $form['upload_location'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Upload location'),
      '#default_value' => $configuration['upload_location'],
    ];

    $form['dropzone_description'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Dropzone drag-n-drop zone text'),
      '#default_value' => $configuration['dropzone_description'],
    ];

    preg_match('%\d+%', $configuration['max_filesize'], $matches);
    $max_filesize = !empty($matches) ? array_shift($matches) : '1';

    $form['max_filesize'] = [
      '#type' => 'number',
      '#title' => $this->t('Maximum size of files'),
      '#min' => '0',
      '#field_suffix' => $this->t('MB'),
      '#default_value' => $max_filesize,
    ];

    $form['extensions'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Allowed file extensions'),
      '#desciption' => $this->t('A space separated list of file extensions'),
      '#default_value' => $configuration['extensions'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues()['table'][$this->uuid()]['form'];

    if (!empty($values['extensions'])) {
      $extensions = explode(' ', $values['extensions']);
      $fail = FALSE;

      foreach ($extensions as $extension) {
        if (preg_match('%^\w*$%', $extension) !== 1) {
          $fail = TRUE;
        }
      }

      if ($fail) {
        $form_state->setErrorByName('extensions', $this->t('Invalid extension list format.'));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);
    $this->configuration['max_filesize'] = $this->configuration['max_filesize'] . 'M';
  }

  /**
   * {@inheritdoc}
   */
  public function __sleep() {
    return array_diff(parent::__sleep(), ['files']);
  }

}
