<?php

namespace Drupal\webform;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\webform\Plugin\WebformHandlerInterface;
use Drupal\webform\Utility\WebformArrayHelper;
use Drupal\webform\Utility\WebformDateHelper;
use Drupal\webform\Utility\WebformElementHelper;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a webform to manage settings.
 */
class WebformEntitySettingsForm extends EntityForm {

  /**
   * The webform submission storage.
   *
   * @var \Drupal\webform\WebformSubmissionStorageInterface
   */
  protected $submissionStorage;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The webform message manager.
   *
   * @var \Drupal\webform\WebformMessageManagerInterface
   */
  protected $messageManager;

  /**
   * The webform token manager.
   *
   * @var \Drupal\webform\WebformTokenManagerInterface
   */
  protected $tokenManager;

  /**
   * The webform third party settings manager.
   *
   * @var \Drupal\webform\WebformThirdPartySettingsManagerInterface
   */
  protected $thirdPartySettingsManager;

  /**
   * Constructs a WebformEntitySettingsForm.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\webform\WebformMessageManagerInterface $message_manager
   *   The webform message manager.
   * @param \Drupal\webform\WebformTokenManagerInterface $token_manager
   *   The webform token manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, AccountInterface $current_user, WebformMessageManagerInterface $message_manager, WebformTokenManagerInterface $token_manager, WebformThirdPartySettingsManagerInterface $third_party_settings_manager) {
    $this->submissionStorage = $entity_type_manager->getStorage('webform_submission');
    $this->currentUser = $current_user;
    $this->messageManager = $message_manager;
    $this->tokenManager = $token_manager;
    $this->thirdPartySettingsManager = $third_party_settings_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('current_user'),
      $container->get('webform.message_manager'),
      $container->get('webform.token_manager'),
      $container->get('webform.third_party_settings_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\webform\WebformInterface $webform */
    $webform = $this->entity;

    // Set message manager's webform.
    $this->messageManager->setWebform($webform);

    $default_settings = $this->config('webform.settings')->get('settings');
    $settings = $webform->getSettings();

    // General settings.
    $form['general_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('General settings'),
      '#open' => TRUE,
    ];
    $form['general_settings']['id'] = [
      '#type' => 'item',
      '#title' => $this->t('ID'),
      '#markup' => $webform->id(),
      '#value' => $webform->id(),
    ];
    $form['general_settings']['title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Title'),
      '#maxlength' => 255,
      '#default_value' => $webform->label(),
      '#required' => TRUE,
      '#id' => 'title',
    ];
    $form['general_settings']['description'] = [
      '#type' => 'webform_html_editor',
      '#title' => $this->t('Administrative description'),
      '#default_value' => $webform->get('description'),
    ];
    /** @var \Drupal\webform\WebformEntityStorageInterface $webform_storage */
    $webform_storage = $this->entityTypeManager->getStorage('webform');
    $form['general_settings']['category'] = [
      '#type' => 'webform_select_other',
      '#title' => $this->t('Category'),
      '#options' => $webform_storage->getCategories(),
      '#empty_option' => '<' . $this->t('None') . '>',
      '#default_value' => $webform->get('category'),
    ];
    $form['general_settings']['template'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Allow this webform to be used as a template.'),
      '#description' => $this->t('If checked, this webform will be available as a template to all users who can create new webforms.'),
      '#return_value' => TRUE,
      '#access' => $this->moduleHandler->moduleExists('webform_templates'),
      '#default_value' => $webform->isTemplate(),
    ];
    $form['general_settings']['results_disabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Disable saving of submissions.'),
      '#description' => $this->t('If saving of submissions is disabled, submission settings, submission limits, purging and the saving of drafts will be disabled. Submissions must be sent via an email or handled using a custom <a href=":href">webform handler</a>.', [':href' => Url::fromRoute('entity.webform.handlers_form', ['webform' => $webform->id()])->toString()]),
      '#return_value' => TRUE,
      '#default_value' => $settings['results_disabled'],
    ];

    // Display warning when submission handler requires submissions to be saved
    // to the database.
    $is_submission_required = $webform->getHandlers(NULL, TRUE, NULL, WebformHandlerInterface::SUBMISSION_REQUIRED)->count();
    if ($is_submission_required) {
      $form['general_settings']['results_disabled']['#default_value'] = FALSE;
      $form['general_settings']['results_disabled']['#disabled'] = TRUE;
      unset($form['general_settings']['results_disabled']['#description']);
      $form['general_settings']['results_disabled_required'] = [
        '#type' => 'webform_message',
        '#message_type' => 'warning',
        '#message_message' => $this->messageManager->get(WebformMessageManagerInterface::HANDLER_SUBMISSION_REQUIRED),
      ];
    }

    // Display warning when disabling the saving of submissions with no
    // handlers.
    $is_results_processed = $webform->getHandlers(NULL, TRUE, WebformHandlerInterface::RESULTS_PROCESSED)->count();
    if (!$is_results_processed) {
      $form['general_settings']['results_disabled_error'] = [
        '#type' => 'webform_message',
        '#message_type' => 'warning',
        '#message_message' => $this->messageManager->get(WebformMessageManagerInterface::FORM_SAVE_EXCEPTION),
        '#states' => [
          'visible' => [
            ':input[name="results_disabled"]' => ['checked' => TRUE],
            ':input[name="results_disabled_ignore"]' => ['checked' => FALSE],
          ],
        ],
      ];
      $form['general_settings']['results_disabled_ignore'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Ignore disabled results warning'),
        '#description' => $this->t("If checked, all warnings and log messages about 'This webform is currently not saving any submitted data.' will be suppressed."),
        '#return_value' => TRUE,
        '#default_value' => $settings['results_disabled_ignore'],
        '#states' => [
          'visible' => [
            ':input[name="results_disabled"]' => ['checked' => TRUE],
          ],
        ],
      ];
    }

    // Page settings.
    $form['page_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('URL path settings'),
    ];
    $default_page_submit_path = trim($default_settings['default_page_base_path'], '/') . '/' . str_replace('_', '-', $webform->id());
    $t_args = [
      ':node_href' => ($this->moduleHandler->moduleExists('node')) ? Url::fromRoute('node.add', ['node_type' => 'webform'])->toString() : '',
      ':block_href' => ($this->moduleHandler->moduleExists('block')) ? Url::fromRoute('block.admin_display')->toString() : '',
    ];
    $default_settings['default_page_submit_path'] = $default_page_submit_path;
    $default_settings['default_page_confirm_path'] = $default_page_submit_path . '/confirmation';
    $form['page_settings']['page'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Allow users to post submission from a dedicated URL.'),
      '#description' => $this->t('If unchecked, this webform must be attached to a <a href=":node_href">node</a> or a <a href=":block_href">block</a> to receive submissions.', $t_args),
      '#return_value' => TRUE,
      '#default_value' => $settings['page'],
    ];
    if ($this->moduleHandler->moduleExists('path')) {
      $form['page_settings']['page_submit_path'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Webform URL alias'),
        '#description' => $this->t('Optionally specify an alternative URL by which the webform submit page can be accessed.', $t_args),
        '#default_value' => $settings['page_submit_path'],
        '#states' => [
          'visible' => [
            ':input[name="page"]' => ['checked' => TRUE],
          ],
        ],
      ];
      $form['page_settings']['page_confirm_path'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Confirmation page URL alias'),
        '#description' => $this->t('Optionally specify an alternative URL by which the webform confirmation page can be accessed.', $t_args),
        '#default_value' => $settings['page_confirm_path'],
        '#states' => [
          'visible' => [
            ':input[name="page"]' => ['checked' => TRUE],
          ],
        ],
      ];
    }

    // Form settings.
    $form['form_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Form settings'),
    ];
    $form['form_settings']['status'] = [
      '#type' => 'radios',
      '#title' => $this->t('Form status'),
      '#default_value' => $webform->get('status'),
      '#options' => [
        WebformInterface::STATUS_OPEN => $this->t('Open'),
        WebformInterface::STATUS_CLOSED => $this->t('Closed'),
        WebformInterface::STATUS_SCHEDULED => $this->t('Scheduled'),
      ],
    ];

    // @see \Drupal\webform\Plugin\Field\FieldWidget\WebformEntityReferenceAutocompleteWidget::formElement
    $form['form_settings']['scheduled'] = [
      '#type' => 'item',
      '#input' => FALSE,
      '#description' => $this->t('If the open date/time is left blank, this form will immediately be opened.') .
      '<br />' .
      $this->t('If the close date/time is left blank, this webform will never be closed.'),
      '#states' => [
        'visible' => [
          ':input[name="status"]' => ['value' => WebformInterface::STATUS_SCHEDULED],
        ],
      ],
    ];
    $form['form_settings']['scheduled']['open'] = [
      '#type' => 'datetime',
      '#title' => $this->t('Open'),
      '#prefix' => '<div class="container-inline form-item">',
      '#suffix' => '</div>',
      '#default_value' => $webform->get('open') ? DrupalDateTime::createFromTimestamp(strtotime($webform->get('open'))) : NULL,
    ];
    $form['form_settings']['scheduled']['close'] = [
      '#type' => 'datetime',
      '#title' => $this->t('Close'),
      '#prefix' => '<div class="container-inline form-item">',
      '#suffix' => '</div>',
      '#default_value' => $webform->get('close') ? DrupalDateTime::createFromTimestamp(strtotime($webform->get('close'))) : NULL,
    ];
    // If the Webform templates module is enabled, add additional #states.
    if ($this->moduleHandler->moduleExists('webform_templates')) {
      $form['form_settings']['status']['#states'] = [
        'visible' => [
          ':input[name="template"]' => ['checked' => FALSE],
        ],
      ];
      $form['form_settings']['scheduled']['#states']['visible'][':input[name="template"]'] = ['checked' => FALSE];
    }
    $form['form_settings']['form_open_message'] = [
      '#type' => 'webform_html_editor',
      '#title' => $this->t('Form open message'),
      '#description' => $this->t('A message to be displayed notifying the user that the webform is going to be opening to submissions. The opening message will only be displayed when a webform is scheduled to be opened.'),
      '#default_value' => $settings['form_open_message'],
    ];
    $form['form_settings']['form_close_message'] = [
      '#type' => 'webform_html_editor',
      '#title' => $this->t('Form closed message'),
      '#description' => $this->t("A message to be displayed notifying the user that the webform is closed. The closed message will be displayed when a webform's status is closed or a submission limit is reached."),
      '#default_value' => $settings['form_close_message'],
    ];
    $form['form_settings']['form_exception_message'] = [
      '#type' => 'webform_html_editor',
      '#title' => $this->t('Form exception message'),
      '#description' => $this->t('A message to be displayed if the webform breaks.'),
      '#default_value' => $settings['form_exception_message'],
    ];
    $elements = $webform->getElementsDecoded();
    $form['form_settings']['attributes'] = [
      '#type' => 'webform_element_attributes',
      '#title' => $this->t('Form'),
      '#classes' => $this->configFactory->get('webform.settings')->get('settings.form_classes'),
      '#default_value' => (isset($elements['#attributes'])) ? $elements['#attributes'] : [],
    ];

    // Form behaviors.
    $behavior_elements = [
      // Global behaviors.
      // @see \Drupal\webform\Form\WebformAdminSettingsForm
      'form_submit_once' => [
        'title' => $this->t('Prevent duplicate submissions'),
        'all_description' => $this->t('Submit button is disabled immediately after it is clicked for all forms.'),
        'form_description' => $this->t('If checked, the submit button will be disabled immediately after it is clicked.'),
      ],
      'form_disable_back' => [
        'title' => $this->t('Disable back button'),
        'all_description' => $this->t('Back button is disabled for all forms.'),
        'form_description' => $this->t('If checked, users will not be allowed to navigate back to the form using the browsers back button.'),
      ],
      'form_unsaved' => [
        'title' => $this->t('Warn users about unsaved changes'),
        'all_description' => $this->t('Unsaved warning is enabled for all forms.'),
        'form_description' => $this->t('If checked, users will be displayed a warning message when they navigate away from a form with unsaved changes.'),
      ],
      'form_novalidate' => [
        'title' => $this->t('Disable client-side validation'),
        'all_description' => $this->t('Client-side validation is disabled for all forms.'),
        'form_description' => $this->t('If checked, the <a href=":href">novalidate</a> attribute, which disables client-side validation, will be added to this form.', [':href' => 'http://www.w3schools.com/tags/att_form_novalidate.asp']),
      ],
      'form_details_toggle' => [
        'title' => $this->t('Display collapse/expand all details link'),
        'all_description' => $this->t('Expand/collapse all (details) link is automatically added to all forms.'),
        'form_description' => $this->t('If checked, an expand/collapse all (details) link will be added to this webform when there are two or more details elements available on the webform.'),
      ],
      // Form specific behaviors.
      'form_disable_autocomplete' => [
        'title' => $this->t('Disable autocompletion'),
        'form_description' => $this->t('If checked, the <a href=":href">autocomplete</a> attribute will be set to off, which disables autocompletion for all form elements.', [':href' => 'http://www.w3schools.com/tags/att_form_autocomplete.asp']),
      ],
      'form_autofocus' => [
        'title' => $this->t('Autofocus'),
        'form_description' => $this->t('If checked, the first visible and enabled input will be focused when adding new submissions.'),
      ],
      'form_prepopulate' => [
        'title' => $this->t('Allow elements to be populated using query string parameters.'),
        'form_description' => $this->t("If checked, elements can be populated using query string parameters. For example, appending ?name=John+Smith to a webform's URL would setting an the 'name' element's default value to 'John Smith'."),
      ],
      'form_prepopulate_source_entity' => [
        'title' => $this->t('Allow source entity to be populated using query string parameters.'),
        'form_description' => $this->t("If checked, source entity can be populated using query string parameters. For example, appending ?source_entity_type=node&source_entity_id=1 to a webform's URL would set a submission's 'Submitted to' value to 'node:1'."),
      ],
      'form_prepopulate_source_entity_required' => [
        'title' => $this->t('Require source entity to be populated using query string parameters.'),
        'form_description' => $this->t("If checked, source entity must be populated using query string parameters."),
      ],
    ];
    $form['form_behaviors'] = [
      '#type' => 'details',
      '#title' => $this->t('Form behaviors'),
    ];
    $this->appendBehaviors($form['form_behaviors'], $behavior_elements, $settings, $default_settings);
    $form['form_behaviors']['form_prepopulate_source_entity_required']['#states'] = [
      'visible' => [':input[name="form_prepopulate_source_entity"]' => ['checked' => TRUE]],
    ];
    $entity_type_options = [];
    $entity_type_manager = \Drupal::entityTypeManager();
    foreach ($entity_type_manager->getDefinitions() as $entity_type_id => $entity_type) {
      $entity_type_options[$entity_type_id] = $entity_type->getLabel();
    }
    $form['form_behaviors']['form_prepopulate_source_entity_type'] = [
      '#type' => 'select',
      '#title' => 'Type of source entity to be populated using query string parameters.',
      '#weight' => ++$form['form_behaviors']['form_prepopulate_source_entity_required']['#weight'],
      '#empty_option' => '',
      '#options' => $entity_type_options,
      '#states' => [
        'visible' => [':input[name="form_prepopulate_source_entity"]' => ['checked' => TRUE]],
      ],
    ];

    // Wizard settings.
    $form['wizard_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Wizard settings'),
      '#states' => [
        'visible' => [
          ':input[name="method"]' => ['value' => ''],
        ],
      ],
    ];
    $form['wizard_settings']['wizard_progress_bar'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show wizard progress bar'),
      '#return_value' => TRUE,
      '#default_value' => $settings['wizard_progress_bar'],
    ];
    $form['wizard_settings']['wizard_progress_pages'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show wizard progress pages'),
      '#return_value' => TRUE,
      '#default_value' => $settings['wizard_progress_pages'],
    ];
    $form['wizard_settings']['wizard_progress_percentage'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show wizard progress percentage'),
      '#return_value' => TRUE,
      '#default_value' => $settings['wizard_progress_percentage'],
    ];
    $form['wizard_settings']['wizard_complete'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Include confirmation page in progress'),
      '#return_value' => TRUE,
      '#default_value' => $settings['wizard_complete'],
    ];
    $form['wizard_settings']['wizard_start_label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Wizard start label'),
      '#size' => 20,
      '#default_value' => $settings['wizard_start_label'],
    ];
    $form['wizard_settings']['wizard_complete_label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Wizard end label'),
      '#size' => 20,
      '#default_value' => $settings['wizard_complete_label'],
      '#states' => [
        'visible' => [
          ':input[name="wizard_complete"]' => ['checked' => TRUE],
        ],
      ],
    ];

    // Preview settings.
    $form['preview_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Preview settings'),
      '#states' => [
        'visible' => [
          ':input[name="method"]' => ['value' => ''],
        ],
      ],
    ];
    $form['preview_settings']['preview'] = [
      '#type' => 'radios',
      '#title' => $this->t('Enable preview page'),
      '#options' => [
        DRUPAL_DISABLED => $this->t('Disabled'),
        DRUPAL_OPTIONAL => $this->t('Optional'),
        DRUPAL_REQUIRED => $this->t('Required'),
      ],
      '#description' => $this->t('Add a page for previewing the webform before submitting.'),
      '#default_value' => $settings['preview'],
    ];
    $form['preview_settings']['preview_container'] = [
      '#type' => 'container',
      '#states' => [
        'invisible' => [
          ':input[name="preview"]' => ['value' => DRUPAL_DISABLED],
        ],
      ],
    ];
    $form['preview_settings']['preview_container']['preview_label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Preview label'),
      '#description' => $this->t('A text displayed within a wizard progress bar.'),
      '#default_value' => $settings['preview_label'],
    ];
    $form['preview_settings']['preview_container']['preview_title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Preview page title'),
      '#description' => $this->t('A title displayed on the preview page.'),
      '#default_value' => $settings['preview_title'],
    ];
    $form['preview_settings']['preview_container']['preview_message'] = [
      '#type' => 'webform_html_editor',
      '#title' => $this->t('Preview message'),
      '#description' => $this->t('A message to be displayed on the preview page.'),
      '#default_value' => $settings['preview_message'],
    ];
    $form['preview_settings']['preview_container']['preview_attributes'] = [
      '#type' => 'webform_element_attributes',
      '#title' => $this->t('Preview'),
      '#classes' => $this->configFactory->get('webform.settings')->get('settings.preview_classes'),
      '#default_value' => $settings['preview_attributes'],
    ];
    // Elements.
    $form['preview_settings']['preview_container']['elements'] = [
      '#type' => 'details',
      '#title' => $this->t('Included preview values'),
      '#description' => $this->t('If you wish to include only parts of the submission in the preview, select the elements that should be included. Please note, element specific access controls are still applied to displayed elements.'),
      '#open' => $settings['preview_excluded_elements'] ? TRUE : FALSE,
    ];
    $form['preview_settings']['preview_container']['elements']['preview_excluded_elements'] = [
      '#type' => 'webform_excluded_elements',
      '#webform_id' => $this->getEntity()->id(),
      '#default_value' => $settings['preview_excluded_elements'],
    ];

    // Draft settings.
    $form['draft_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Draft settings'),
      '#states' => [
        'visible' => [
          ':input[name="results_disabled"]' => ['checked' => FALSE],
          ':input[name="method"]' => ['value' => ''],
        ],
      ],
    ];
    $form['draft_settings']['draft'] = [
      '#type' => 'radios',
      '#title' => $this->t('Allow your users to save and finish the webform later'),
      '#default_value' => $settings['draft'],
      '#options' => [
        WebformInterface::DRAFT_NONE => $this->t('Disabled'),
        WebformInterface::DRAFT_AUTHENTICATED => $this->t('Authenticated users'),
        WebformInterface::DRAFT_ALL => $this->t('Authenticated and anonymous users'),
      ],
    ];
    $form['draft_settings']['draft_message'] = [
      '#type' => 'webform_message',
      '#message_type' => 'warning',
      '#message_message' => $this->t('Please make sure to enable the <a href=":href">automatic purging of draft submissions</a>, to ensure that your database is not filled with abandoned anonymous submissions in draft.', [':href' => Url::fromRoute('<none>', [], ['fragment' => 'edit-purge'])->toString()]),
      '#states' => [
        'visible' => [
          ':input[name="draft"]' => ['value' => WebformInterface::DRAFT_ALL],
          ':input[name="purge"]' => [
            ['value' => WebformSubmissionStorageInterface::PURGE_NONE],
            ['value' => WebformSubmissionStorageInterface::PURGE_COMPLETED],
          ],
        ],
      ],
    ];
    $form['draft_settings']['draft_container'] = [
      '#type' => 'container',
      '#states' => [
        'invisible' => [
          ':input[name="draft"]' => ['value' => WebformInterface::DRAFT_NONE],
        ],
      ],
    ];
    $form['draft_settings']['draft_container']['draft_multiple'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Allow users to save multiple drafts.'),
      "#description" => $this->t('If checked, users will be able saved and resume multiple drafts.'),
      '#return_value' => TRUE,
      '#default_value' => $settings['draft_multiple'],
    ];
    $form['draft_settings']['draft_container']['draft_auto_save'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Automatically save as draft when paging, previewing, and when there are validation errors.'),
      "#description" => $this->t('Automatically save partial submissions when users click the "Preview" button or when validation errors prevent a webform from being submitted.'),
      '#return_value' => TRUE,
      '#default_value' => $settings['draft_auto_save'],
    ];
    $form['draft_settings']['draft_container']['draft_saved_message'] = [
      '#type' => 'webform_html_editor',
      '#title' => $this->t('Draft saved message'),
      '#description' => $this->t('Message to be displayed when a draft is saved.'),
      '#default_value' => $settings['draft_saved_message'],
    ];
    $form['draft_settings']['draft_container']['draft_loaded_message'] = [
      '#type' => 'webform_html_editor',
      '#title' => $this->t('Draft loaded message'),
      '#description' => $this->t('Message to be displayed when a draft is loaded.'),
      '#default_value' => $settings['draft_loaded_message'],
    ];

    // Submission settings.
    $form['submission_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Submission settings'),
      '#states' => [
        'visible' => [
          ':input[name="results_disabled"]' => ['checked' => FALSE],
          ':input[name="method"]' => ['value' => ''],
        ],
      ],
    ];
    $form['submission_settings']['submission_label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Submission label'),
      '#default_value' => $settings['submission_label'],
    ];
    $form['submission_settings']['next_serial'] = [
      '#type' => 'number',
      '#title' => $this->t('Next submission number'),
      '#description' => $this->t('The value of the next submission number. This is usually 1 when you start and will go up with each webform submission.'),
      '#min' => 1,
      '#default_value' => $webform_storage->getNextSerial($webform),
    ];
    $form['submission_settings']['token_tree_link'] = $this->tokenManager->buildTreeLink();
    $form['submission_settings']['submission_columns'] = [
      '#type' => 'details',
      '#title' => $this->t('Submission columns'),
      '#description' => $this->t('Below columns are displayed to users who can view previous submissions and/or pending drafts.'),
    ];
    // Submission user columns.
    // @see \Drupal\webform\Form\WebformResultsCustomForm::buildForm
    $available_columns = $this->submissionStorage->getColumns($webform);
    // Remove columns that should never be displayed to users.
    $available_columns = array_diff_key($available_columns, array_flip(['uuid', 'in_draft', 'entity', 'sticky', 'notes', 'uid', 'operations']));
    $custom_columns = $this->submissionStorage->getUserColumns($webform);
    // Change sid's # to an actual label.
    $available_columns['sid']['title'] = $this->t('Submission ID');
    if (isset($custom_columns['sid'])) {
      $custom_columns['sid']['title'] = $this->t('Submission ID');
    }
    // Get available columns as option.
    $columns_options = [];
    foreach ($available_columns as $column_name => $column) {
      $title = (strpos($column_name, 'element__') === 0) ? ['data' => ['#markup' => '<b>' . $column['title'] . '</b>']] : $column['title'];
      $key = (isset($column['key'])) ? str_replace('webform_', '', $column['key']) : $column['name'];
      $columns_options[$column_name] = ['title' => $title, 'key' => $key];
    }
    // Get custom columns as the default value.
    $columns_keys = array_keys($custom_columns);
    $columns_default_value = array_combine($columns_keys, $columns_keys);
    // Display columns in sortable table select element.
    $form['submission_settings']['submission_columns']['submission_user_columns'] = [
      '#type' => 'webform_tableselect_sort',
      '#header' => [
        'title' => $this->t('Title'),
        'key' => $this->t('Key'),
      ],
      '#options' => $columns_options,
      '#default_value' => $columns_default_value,
    ];

    // Submission behaviors.
    $form['submission_behaviors'] = [
      '#type' => 'details',
      '#title' => $this->t('Submission behaviors'),
      '#states' => [
        'visible' => [
          ':input[name="results_disabled"]' => ['checked' => FALSE],
          ':input[name="method"]' => ['value' => ''],
        ],
      ],
    ];
    $form['submission_behaviors']['form_confidential'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Confidential submissions'),
      '#description' => $this->t('Confidential submissions have no recorded IP address and must be submitted while logged out.'),
      '#return_value' => TRUE,
      '#default_value' => $settings['form_confidential'],
    ];
    $form['submission_behaviors']['form_confidential_message'] = [
      '#type' => 'webform_html_editor',
      '#title' => $this->t('Webform confidential message'),
      '#description' => $this->t('A message to be displayed when authenticated users try to access a confidential webform.'),
      '#default_value' => $settings['form_confidential_message'],
      '#states' => [
        'visible' => [
          ':input[name="form_confidential"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['submission_behaviors']['form_convert_anonymous'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Convert anonymous user drafts and submissions to authenticated user.'),
      '#description' => $this->t('If checked, drafts and submissions created by an anonymous user will be reassigned to their user account when they login.'),
      '#return_value' => TRUE,
      '#default_value' => $settings['form_convert_anonymous'],
      '#states' => [
        'visible' => [
          ':input[name="form_confidential"]' => ['checked' => FALSE],
        ],
      ],
    ];
    $behavior_elements = [
      // Form specific behaviors.
      'form_previous_submissions' => [
        'title' => $this->t('Show the notification about previous submissions'),
        'form_description' => $this->t('Show the previous submissions notification that appears when users have previously submitted this form.'),
      ],
      'token_update' => [
        'title' => $this->t('Allow users to update a submission using a secure token.'),
        'form_description' => $this->t("If checked users will be able to update a submission using the webform's URL appended with the submission's (secure) token.  The URL to update a submission will be available when viewing a submission's information and can be inserted into the an email using the [webform_submission:update-url] token."),
      ],
      // Global behaviors.
      // @see \Drupal\webform\Form\WebformAdminSettingsForm
      'submission_log' => [
        'title' => $this->t('Log submission events'),
        'all_description' => $this->t('All submission event are being logged for all webforms.'),
        'form_description' => $this->t('If checked, events will be logged for submissions to this webforms.'),
      ],
    ];
    $this->appendBehaviors($form['submission_behaviors'], $behavior_elements, $settings, $default_settings);

    // Submission limits.
    $form['submission_limits'] = [
      '#type' => 'details',
      '#title' => $this->t('Submission limits'),
      '#states' => [
        'visible' => [
          ':input[name="results_disabled"]' => ['checked' => FALSE],
          ':input[name="method"]' => ['value' => ''],
        ],
      ],
    ];
    $form['submission_limits']['total'] = [
      '#type' => 'details',
      '#title' => $this->t('Total submissions'),
    ];
    $form['submission_limits']['total']['limit_total'] = [
      '#type' => 'number',
      '#title' => $this->t('Total submissions limit'),
      '#min' => 1,
      '#default_value' => $settings['limit_total'],
    ];
    $form['submission_limits']['total']['entity_limit_total'] = [
      '#type' => 'number',
      '#title' => $this->t('Total submissions limit per source entity'),
      '#min' => 1,
      '#default_value' => $settings['entity_limit_total'],
    ];
    $form['submission_limits']['total']['limit_total_message'] = [
      '#type' => 'webform_html_editor',
      '#title' => $this->t('Total submissions limit message'),
      '#min' => 1,
      '#default_value' => $settings['limit_total_message'],
    ];
    //
    $form['submission_limits']['user'] = [
      '#type' => 'details',
      '#title' => $this->t('Per user'),
      '#description' => $this->t('Limit the number of submissions per user. A user is identified by their user login if logged-in, or by their Cookie if anonymous.'),
    ];
    $form['submission_limits']['user']['limit_user'] = [
      '#type' => 'number',
      '#title' => $this->t('Per user submission limit'),
      '#min' => 1,
      '#default_value' => $settings['limit_user'],
    ];
    $form['submission_limits']['user']['entity_limit_user'] = [
      '#type' => 'number',
      '#min' => 1,
      '#title' => $this->t('Per user submission limit per source entity'),
      '#default_value' => $settings['entity_limit_user'],
    ];
    $form['submission_limits']['user']['limit_user_message'] = [
      '#type' => 'webform_html_editor',
      '#title' => $this->t('Per user submission limit message'),
      '#default_value' => $settings['limit_user_message'],
    ];

    // Purge settings.
    $form['purge_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Submission purging'),
      '#states' => [
        'visible' => [
          ':input[name="results_disabled"]' => ['checked' => FALSE],
          ':input[name="method"]' => ['value' => ''],
        ],
      ],
    ];
    $form['purge_settings']['purge'] = [
      '#type' => 'select',
      '#title' => $this->t('Automatically purge'),
      '#options' => [
        WebformSubmissionStorageInterface::PURGE_NONE => $this->t('None'),
        WebformSubmissionStorageInterface::PURGE_DRAFT => $this->t('Draft'),
        WebformSubmissionStorageInterface::PURGE_COMPLETED => $this->t('Completed'),
        WebformSubmissionStorageInterface::PURGE_ALL => $this->t('Draft and completed'),
      ],
      '#default_value' => $settings['purge'],
    ];
    $form['purge_settings']['purge_days'] = [
      '#type' => 'number',
      '#title' => $this->t('Days to retain submissions'),
      '#min' => 1,
      '#default_value' => $settings['purge_days'],
      '#states' => [
        'invisible' => ['select[name="purge"]' => ['value' => WebformSubmissionStorageInterface::PURGE_NONE]],
        'optional' => ['select[name="purge"]' => ['value' => WebformSubmissionStorageInterface::PURGE_NONE]],
      ],
      '#field_suffix' => $this->t('days'),
    ];

    // Ajax settings.
    $form['ajax_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Ajax settings'),
      '#states' => [
        'visible' => [
          ':input[name="method"]' => ['value' => ''],
        ],
      ],
    ];
    $form['ajax_settings']['ajax'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Use Ajax'),
      '#description' => $this->t('If checked, paging, saving of drafts, previews, submissions, and confirmations will not initiate a page refresh.'),
      '#return_value' => TRUE,
      '#default_value' => $settings['ajax'],
    ];

    // Confirmation settings.
    $form['confirmation_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Confirmation settings'),
      '#states' => [
        'visible' => [
          ':input[name="method"]' => ['value' => ''],
        ],
      ],
    ];
    $form['confirmation_settings']['ajax_confirmation'] = [
      '#type' => 'webform_message',
      '#message_type' => 'warning',
      '#message_message' => $this->t("Only 'Inline' and 'Message' confirmation types are fully supported by Ajax."),
      '#states' => [
        'visible' => [':input[name="ajax"]' => ['checked' => TRUE]],
      ],
    ];
    $form['confirmation_settings']['confirmation_type'] = [
      '#title' => $this->t('Confirmation type'),
      '#type' => 'radios',
      '#options' => [
        'page' => $this->t('Page (redirects to new page and displays the confirmation message)'),
        'inline' => $this->t('Inline (reloads the current page and replaces the webform with the confirmation message.)'),
        'message' => $this->t('Message (reloads the current page/form and displays the confirmation message at the top of the page.)'),
        'url' => $this->t('URL (redirects to a custom path or URL)'),
        'url_message' => $this->t('URL with message (redirects to a custom path or URL and displays the confirmation message at the top of the page.)'),
      ],
      '#default_value' => $settings['confirmation_type'],
    ];
    $form['confirmation_settings']['confirmation_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Confirmation URL'),
      '#description' => $this->t('URL to redirect the user to upon successful submission.'),
      '#default_value' => $settings['confirmation_url'],
      '#maxlength' => NULL,
      '#states' => [
        'visible' => [
          [':input[name="confirmation_type"]' => ['value' => 'url']],
          'or',
          [':input[name="confirmation_type"]' => ['value' => 'url_message']],
        ],
      ],
    ];
    $form['confirmation_settings']['confirmation_title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Confirmation title'),
      '#description' => $this->t('Page title to be shown upon successful submission.'),
      '#default_value' => $settings['confirmation_title'],
      '#states' => [
        'visible' => [
          ':input[name="confirmation_type"]' => ['value' => 'page'],
        ],
      ],
    ];
    $form['confirmation_settings']['confirmation_message'] = [
      '#type' => 'webform_html_editor',
      '#title' => $this->t('Confirmation message'),
      '#description' => $this->t('Message to be shown upon successful submission.'),
      '#default_value' => $settings['confirmation_message'],
      '#states' => [
        'invisible' => [
          ':input[name="confirmation_type"]' => ['value' => 'url'],
        ],
      ],
    ];
    $form['confirmation_settings']['confirmation_page'] = [
      '#type' => 'container',
      '#states' => [
        'visible' => [
          [':input[name="confirmation_type"]' => ['value' => 'page']],
          'or',
          [':input[name="confirmation_type"]' => ['value' => 'inline']],
        ],
      ],
    ];
    $form['confirmation_settings']['confirmation_page']['confirmation_attributes'] = [
      '#type' => 'webform_element_attributes',
      '#title' => $this->t('Confirmation'),
      '#classes' => $this->configFactory->get('webform.settings')->get('settings.confirmation_classes'),
      '#default_value' => $settings['confirmation_attributes'],
    ];
    $form['confirmation_settings']['confirmation_page']['confirmation_back'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Display back to webform link'),
      '#return_value' => TRUE,
      '#default_value' => $settings['confirmation_back'],
    ];
    $form['confirmation_settings']['confirmation_page']['back'] = [
      '#type' => 'details',
      '#title' => $this->t('Confirmation back link'),
      '#states' => [
        'visible' => [
          [':input[name="confirmation_back"]' => ['checked' => TRUE]],
        ],
      ],
    ];
    $form['confirmation_settings']['confirmation_page']['back']['confirmation_back_label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Confirmation back link label'),
      '#size' => 20,
      '#default_value' => $settings['confirmation_back_label'],
    ];
    $form['confirmation_settings']['confirmation_page']['back']['confirmation_back_attributes'] = [
      '#type' => 'webform_element_attributes',
      '#title' => $this->t('Confirmation back link'),
      '#classes' => $this->configFactory->get('webform.settings')->get('settings.confirmation_back_classes'),
      '#default_value' => $settings['confirmation_back_attributes'],
    ];
    $form['confirmation_settings']['token_tree_link'] = $this->tokenManager->buildTreeLink();

    // Author information.
    $form['author_information'] = [
      '#type' => 'details',
      '#title' => $this->t('Author information'),
      '#access' => $this->currentUser()->hasPermission('administer webform'),
    ];
    $form['author_information']['uid'] = [
      '#type' => 'entity_autocomplete',
      '#title' => $this->t('Authored by'),
      '#description' => $this->t("The username of the webform author/owner."),
      '#target_type' => 'user',
      '#settings' => [
        'match_operator' => 'CONTAINS',
      ],
      '#selection_settings' => [
        'include_anonymous' => TRUE,
      ],
      '#default_value' => $webform->getOwner(),
    ];

    // Third party settings.
    $form['third_party_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Third party settings'),
      '#description' => $this->t('Third party settings allow contrib and custom modules to define webform specific customization settings.'),
      '#tree' => TRUE,
    ];
    $this->thirdPartySettingsManager->alter('webform_third_party_settings_form', $form, $form_state);
    if (!Element::children($form['third_party_settings'])) {
      $form['third_party_settings']['#access'] = FALSE;
    }
    else {
      ksort($form['third_party_settings']);
    }

    // Custom settings.
    $properties = WebformElementHelper::getProperties($webform->getElementsDecoded());
    // Set default properties.
    $properties += [
      '#method' => '',
      '#action' => '',
    ];
    $form['custom_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Custom settings'),
      '#open' => array_filter($properties) ? TRUE : FALSE,
      '#access' => !$this->moduleHandler->moduleExists('webform_ui') || $this->currentUser()->hasPermission('edit webform source'),
    ];
    $form['custom_settings']['method'] = [
      '#type' => 'select',
      '#title' => $this->t('Method'),
      '#description' => $this->t('The HTTP method with which the form will be submitted.') . '<br />' .
      '<em>' . $this->t('Selecting a custom POST or GET method will automatically disable wizards, previews, drafts, submissions, limits, purging, and confirmations.') . '</em>',
      '#options' => [
        '' => $this->t('POST (Default)'),
        'post' => $this->t('POST (Custom)'),
        'get' => $this->t('GET (Custom)'),
      ],
      '#default_value' => $properties['#method'],
    ];
    $form['custom_settings']['method_message'] = [
      '#type' => 'webform_message',
      '#message_type' => 'warning',
      '#message_message' => $this->t("Please make sure this webform's action URL or path is setup to handle the webform's submission."),
      '#states' => [
        'invisible' => [
          ':input[name="method"]' => ['value' => ''],
        ],
      ],
    ];

    $form['custom_settings']['action'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Action'),
      '#description' => $this->t('The URL or path to which the webform will be submitted.'),
      '#states' => [
        'invisible' => [
          ':input[name="method"]' => ['value' => ''],
        ],
        'optional' => [
          ':input[name="method"]' => ['value' => ''],
        ],
      ],
      '#default_value' => $properties['#action'],
    ];
    // Unset properties that are webform settings.
    unset(
      $properties['#method'],
      $properties['#action'],
      $properties['#novalidate'],
      $properties['#attributes']
    );
    $form['custom_settings']['custom'] = [
      '#type' => 'webform_codemirror',
      '#mode' => 'yaml',
      '#title' => $this->t('Custom properties'),
      '#description' =>
      $this->t('Properties do not have to prepended with a hash (#) character, the hash character will be automatically added upon submission.') .
      '<br />' .
      $this->t('These properties and callbacks are not allowed: @properties.', ['@properties' => WebformArrayHelper::toString(WebformArrayHelper::addPrefix(WebformElementHelper::$ignoredProperties))]),
      '#default_value' => WebformArrayHelper::removePrefix($properties),
    ];

    $this->appendDefaultValueToElementDescriptions($form, $default_settings);

    return parent::form($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    if ($values['status'] === WebformInterface::STATUS_SCHEDULED) {
      // Require open or close dates.
      if (empty($values['open']) && empty($values['close'])) {
        $form_state->setErrorByName('status', $this->t('Please enter an open or close date'));
      }
      // Make sure open date is not after close date.
      if (!empty($values['open']) && !empty($values['close']) && ($values['open'] > $values['close'])) {
        $form_state->setErrorByName('open', $this->t("The webform's close date cannot be before the open date"));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $values = $form_state->getValues();

    /** @var \Drupal\webform\WebformInterface $webform */
    $webform = $this->getEntity();

    // Set open and close date/time.
    $webform->set('open', NULL);
    $webform->set('close', NULL);
    if ($values['status'] === WebformInterface::STATUS_SCHEDULED) {
      // Massage open/close dates.
      // @see \Drupal\webform\Plugin\Field\FieldWidget\WebformEntityReferenceAutocompleteWidget::massageFormValues
      // @see \Drupal\datetime\Plugin\Field\FieldWidget\DateTimeWidgetBase::massageFormValues
      $states = ['open', 'close'];
      foreach ($states as $state) {
        if (!empty($values[$state]) && $values[$state] instanceof DrupalDateTime) {
          $webform->set($state, WebformDateHelper::formatStorage($values[$state]));
        }
      }
    }

    // Set customize submission user columns.
    $values['submission_user_columns'] = array_values($values['submission_user_columns']);
    if ($values['submission_user_columns'] == $this->submissionStorage->getUserDefaultColumnNames($webform)) {
      $values['submission_user_columns'] = [];
    }

    // Set custom properties, class, and style.
    $elements = $webform->getElementsDecoded();
    $elements = WebformElementHelper::removeProperties($elements);
    $properties = [];
    if (!empty($values['method'])) {
      $properties['#method'] = $values['method'];
    }
    if (!empty($values['action'])) {
      $properties['#action'] = $values['action'];
    }
    if (!empty($values['custom'])) {
      $properties += WebformArrayHelper::addPrefix($values['custom']);
    }
    if (!empty($values['attributes'])) {
      $properties['#attributes'] = $values['attributes'];
    }
    $elements = $properties + $elements;
    $webform->setElements($elements);

    // Remove custom properties and attributes.
    unset(
      $values['method'],
      $values['action'],
      $values['attributes'],
      $values['custom']
    );

    // Set third party settings.
    if (isset($values['third_party_settings'])) {
      $third_party_settings = $values['third_party_settings'];
      foreach ($third_party_settings as $module => $third_party_setting) {
        foreach ($third_party_setting as $key => $value) {
          $webform->setThirdPartySetting($module, $key, $value);
        }
      }
      // Remove third party settings.
      unset($values['third_party_settings']);
    }

    // Set next serial number.
    /** @var \Drupal\webform\WebformEntityStorageInterface $webform_storage */
    $webform_storage = $this->entityTypeManager->getStorage('webform');
    $next_serial = (int) $values['next_serial'];
    $max_serial = $webform_storage->getMaxSerial($webform);
    if ($next_serial < $max_serial) {
      drupal_set_message($this->t('The next submission number was increased to @min to make it higher than existing submissions.', ['@min' => $max_serial]));
      $next_serial = $max_serial;
    }
    $webform_storage->setNextSerial($webform, $next_serial);

    // Remove main properties.
    unset(
      $values['id'],
      $values['title'],
      $values['description'],
      $values['category'],
      $values['template'],
      $values['status'],
      $values['open'],
      $values['close'],
      $values['uid'],
      $values['next_serial']
    );

    // Remove disabled properties.
    unset(
      $values['form_novalidate_disabled'],
      $values['form_unsaved_disabled'],
      $values['form_details_toggle_disabled']
    );

    // Set settings.
    $webform->setSettings($values);

    // Save the webform.
    $webform->save();

    $context = [
      '@label' => $webform->label(),
      'link' => $webform->toLink($this->t('Edit'), 'settings-form')->toString()
    ];
    $this->logger('webform')->notice('Webform settings @label saved.', $context);

    drupal_set_message($this->t('Webform settings %label saved.', ['%label' => $webform->label()]));
  }

  /**
   * Append default value to an element's description.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param array $default_settings
   *   An associative array container default webform settings.
   */
  protected function appendDefaultValueToElementDescriptions(array &$form, array $default_settings) {
    foreach ($form as $key => &$element) {
      // Skip if not a FAPI element.
      if (Element::property($key) || !is_array($element)) {
        continue;
      }

      if (isset($element['#type']) && !empty($default_settings["default_$key"]) && empty($element['#disabled'])) {
        if (!isset($element['#description'])) {
          $element['#description'] = '';
        }
        $element['#description'] .= ($element['#description'] ? '<br />' : '');
        // @todo: Stop quotes from being encoded. (ie "Submit" => &quot;Submit&quote;)
        $value = $default_settings["default_$key"];
        $element['#description'] .= $this->t('Defaults to: %value', ['%value' => $value]);
      }

      $this->appendDefaultValueToElementDescriptions($element, $default_settings);
    }
  }

  /**
   * Append behavior checkboxes to element.
   *
   * @param array $element
   *   An elements
   * @param array $behavior_elements
   *   An associative array of behavior elements.
   * @param array $settings
   *   The webform's settings.
   * @param array $default_settings
   *   The global webform default settings.
   */
  protected function appendBehaviors(array &$element, array $behavior_elements, array $settings, array $default_settings) {
    $weight = 0;
    foreach ($behavior_elements as $behavior_key => $behavior_element) {
      if (!empty($default_settings['default_' . $behavior_key])) {
        $element[$behavior_key . '_disabled'] = [
          '#type' => 'checkbox',
          '#title' => $behavior_element['title'],
          '#description' => $behavior_element['all_description'],
          '#disabled' => TRUE,
          '#default_value' => TRUE,
          '#weight' => $weight,
        ];
        $element[$behavior_key] = [
          '#type' => 'value',
          '#value' => $settings[$behavior_key],
        ];
      }
      else {
        $element[$behavior_key] = [
          '#type' => 'checkbox',
          '#title' => $behavior_element['title'],
          '#description' => $behavior_element['form_description'],
          '#return_value' => TRUE,
          '#default_value' => $settings[$behavior_key],
          '#weight' => $weight,
        ];
      }
      $weight += 10;
    }
  }

}
