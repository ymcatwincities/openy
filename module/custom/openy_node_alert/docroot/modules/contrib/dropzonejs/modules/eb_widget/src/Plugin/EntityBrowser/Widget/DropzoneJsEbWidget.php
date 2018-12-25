<?php

namespace Drupal\dropzonejs_eb_widget\Plugin\EntityBrowser\Widget;

use Drupal\Component\Utility\Bytes;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\InvokeCommand;
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
 *   description = @Translation("Adds DropzoneJS upload integration."),
 *   auto_select = TRUE
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
      'dropzone_description' => $this->t('Drop files here to upload them'),
      'max_filesize' => file_upload_max_size() / pow(Bytes::KILOBYTE, 2) . 'M',
      'extensions' => 'jpg jpeg gif png txt doc xls pdf ppt pps odt ods odp',
      'clientside_resize' => FALSE,
      'resize_width' => NULL,
      'resize_height' => NULL,
      'resize_quality' => 1,
      'resize_method' => 'contain',
      'thumbnail_method' => 'contain',
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
      '#title' => $this->t('File upload'),
      '#type' => 'dropzonejs',
      '#required' => TRUE,
      '#dropzone_description' => $config['settings']['dropzone_description'],
      '#max_filesize' => $config['settings']['max_filesize'],
      '#extensions' => $config['settings']['extensions'],
      '#max_files' => ($cardinality > 0) ? $cardinality : 0,
      '#clientside_resize' => $config['settings']['clientside_resize'],
    ];

    if ($config['settings']['clientside_resize']) {
      $form['upload']['#resize_width'] = $config['settings']['resize_width'];
      $form['upload']['#resize_height'] = $config['settings']['resize_height'];
      $form['upload']['#resize_quality'] = $config['settings']['resize_quality'];
      $form['upload']['#resize_method'] = $config['settings']['resize_method'];
      $form['upload']['#thumbnail_method'] = $config['settings']['thumbnail_method'];
    }

    $form['#attached']['library'][] = 'dropzonejs/widget';
    // Disable the submit button until the upload sucesfully completed.
    $form['#attached']['library'][] = 'dropzonejs_eb_widget/common';
    $original_form['#attributes']['class'][] = 'dropzonejs-disable-submit';

    // Add hidden element used to make execution of auto-select of form.
    if (!empty($config['settings']['auto_select'])) {
      $form['auto_select_handler'] = [
        '#type' => 'hidden',
        '#name' => 'auto_select_handler',
        '#id' => 'auto_select_handler',
        '#attributes' => ['id' => 'auto_select_handler'],
        '#submit' => ['::submitForm'],
        '#executes_submit_callback' => TRUE,
        '#ajax' => [
          'wrapper' => 'auto_select_handler',
          'callback' => [get_class($this), 'handleAjaxCommand'],
          'event' => 'auto_select_enity_browser_widget',
          'progress' => [
            'type' => 'fullscreen',
          ],
        ],
      ];
    }

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

    $files = $form_state->get(['dropzonejs', $this->uuid(), 'files']);

    if (!$files) {
      $files = [];
    }

    // We do some casting because $form_state->getValue() might return NULL.
    foreach ((array) $form_state->getValue(['upload', 'uploaded_files'], []) as $file) {
      if (file_exists($file['path'])) {
        $entity = $this->dropzoneJsUploadSave->createFile(
          $file['path'],
          $this->getUploadLocation(),
          $config['settings']['extensions'],
          $this->currentUser,
          $additional_validators
        );
        $files[] = $entity;
      }
    }

    if ($form['widget']['upload']['#max_files']) {
      $files = array_slice($files, -$form['widget']['upload']['#max_files']);
    }

    $form_state->set(['dropzonejs', $this->uuid(), 'files'], $files);

    return $files;
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
    if (($trigger['#type'] == 'submit' && $trigger['#name'] == 'op') || $trigger['#name'] === 'auto_select_handler') {
      $upload_location = $this->getUploadLocation();
      if (!file_prepare_directory($upload_location, FILE_CREATE_DIRECTORY | FILE_MODIFY_PERMISSIONS)) {
        $form_state->setError($form['widget']['upload'], $this->t('Files could not be uploaded because the destination directory %destination is not configured correctly.', ['%destination' => $this->getConfiguration()['settings']['upload_location']]));
      }

      $files = $this->getFiles($form, $form_state);
      if (in_array(FALSE, $files)) {
        // @todo Output the actual errors from validateFile.
        $form_state->setError($form['widget']['upload'], $this->t('Some files that you are trying to upload did not pass validation. Requirements are: max file %size, allowed extensions are %extensions', ['%size' => $this->getConfiguration()['settings']['max_filesize'], '%extensions' => $this->getConfiguration()['settings']['extensions']]));
      }

      if (empty($files)) {
        $form_state->setError($form['widget']['upload'], $this->t('At least one valid file should be uploaded.'));
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

    $this->selectEntities($files, $form_state);
    $this->clearFormValues($element, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function selectEntities(array $entities, FormStateInterface $form_state) {
    if (!empty(array_filter($entities))) {
      $config = $this->getConfiguration();

      if (empty($config['settings']['auto_select'])) {
        parent::selectEntities($entities, $form_state);
      }
    }

    $form_state->set(['dropzonejs', 'added_entities'], $entities);
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
    $form_state->set(['dropzonejs', $this->uuid(), 'files'], []);
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


    $exif_found = \Drupal::service('library.discovery')->getLibraryByName('dropzonejs', 'exif-js');

    $form['clientside_resize'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Use client side resizing'),
      '#default_value' => $configuration['clientside_resize'],
    ];

    if (!$exif_found) {
      $form['clientside_resize']['#description'] = $this->t('Requires droopzone version v4.4.0 or higher and the <a href="@exif" target="_blank">exif</a> library.', ['@exif' => 'https://github.com/exif-js/exif-js']);

      // We still want to provide a way to disable this if the library does not
      // exist.
      if ($configuration['clientside_resize'] == FALSE) {
        $form['clientside_resize']['#disabled'] = TRUE;
      }
    }

    $form['resize_width'] = [
      '#type' => 'number',
      '#title' => $this->t('Max width'),
      '#default_value' => $configuration['resize_width'],
      '#size' => 60,
      '#field_suffix' => 'px',
      '#min' => 0,
      '#states' => [
        'visible' => [
          ':input[name="table[' . $this->uuid() .  '][form][clientside_resize]"]' => [
            'checked' => TRUE,
          ],
        ]
      ]
    ];

    $form['resize_height'] = [
      '#type' => 'number',
      '#title' => $this->t('Max height'),
      '#default_value' => $configuration['resize_height'],
      '#size' => 60,
      '#field_suffix' => 'px',
      '#min' => 0,
      '#states' => [
        'visible' => [
          ':input[name="table[' . $this->uuid() .  '][form][clientside_resize]"]' => [
            'checked' => TRUE,
          ],
        ]
      ]
    ];

    $form['resize_quality'] = [
      '#type' => 'number',
      '#title' => $this->t('Resize quality'),
      '#default_value' => $configuration['resize_quality'],
      '#min' => 0,
      '#max' => 1,
      '#step' => 0.1,
      '#states' => [
        'visible' => [
          ':input[name="table[' . $this->uuid() .  '][form][clientside_resize]"]' => [
            'checked' => TRUE,
          ],
        ]
      ]
    ];

    $form['resize_method'] = [
      '#type' => 'select',
      '#title' => $this->t('Resize method'),
      '#default_value' => $configuration['resize_method'],
      '#options' => [
        'contain' => $this->t('Contain (scale)'),
        'crop' => $this->t('Crop'),
      ],
      '#states' => [
        'visible' => [
          ':input[name="table[' . $this->uuid() .  '][form][clientside_resize]"]' => [
            'checked' => TRUE,
          ],
        ]
      ]
    ];

    $form['thumbnail_method'] = [
      '#type' => 'select',
      '#title' => $this->t('Thumbnail method'),
      '#default_value' => $configuration['thumbnail_method'],
      '#options' => [
        'contain' => $this->t('Contain (scale)'),
        'crop' => $this->t('Crop'),
      ],
      '#states' => [
        'visible' => [
          ':input[name="table[' . $this->uuid() .  '][form][clientside_resize]"]' => [
            'checked' => TRUE,
          ],
        ]
      ]
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

  /**
   * Handling of automated submit of uploaded files.
   *
   * @param array $form
   *   Form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse|array
   *   Returns ajax commands that will be executed in front-end.
   */
  public static function handleAjaxCommand(array $form, FormStateInterface $form_state) {
    // If there are some errors during submitting of form they should be
    // displayed, that's why we are returning status message here and generated
    // errors will be displayed properly in front-end.
    if (count($form_state->getErrors()) > 0) {
      return [
        '#type' => 'status_messages',
      ];
    }

    // Output correct response if everything passed without any error.
    $ajax = new AjaxResponse();

    if (($triggering_element = $form_state->getTriggeringElement()) && $triggering_element['#name'] === 'auto_select_handler') {
      $entity_ids = [];

      $added_entities = $form_state->get(['dropzonejs', 'added_entities']);
      /** @var \Drupal\Core\Entity\EntityInterface $entity */
      if (!empty($added_entities)) {
        foreach ($added_entities as $entity) {
          $entity_ids[] = $entity->getEntityTypeId() . ':' . $entity->id();
        }
      }

      // Add command to clear list of uploaded files. It's important to set
      // empty string value, in other case it will act as getter.
      $ajax->addCommand(
        new InvokeCommand('[data-drupal-selector="edit-upload-uploaded-files"]', 'val', [''])
      );

      // Add Invoke command to select uploaded entities.
      $ajax->addCommand(
        new InvokeCommand('.entities-list', 'trigger', [
          'add-entities',
          [$entity_ids],
        ])
      );
    }

    return $ajax;
  }

}
