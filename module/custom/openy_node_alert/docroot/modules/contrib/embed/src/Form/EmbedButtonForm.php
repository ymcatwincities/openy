<?php

namespace Drupal\embed\Form;

use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\embed\EmbedType\EmbedTypeManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form controller for embed button forms.
 */
class EmbedButtonForm extends EntityForm {

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The embed type plugin manager.
   *
   * @var \Drupal\embed\EmbedType\EmbedTypeManager
   */
  protected $embedTypeManager;

  /**
   * Constructs a new EmbedButtonForm.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\embed\EmbedType\EmbedTypeManager $embed_type_manager
   *   The embed type plugin manager.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, EmbedTypeManager $embed_type_manager, ConfigFactoryInterface $config_factory) {
    $this->entityTypeManager = $entity_type_manager;
    $this->embedTypeManager = $embed_type_manager;
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('plugin.manager.embed.type'),
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    /** @var \Drupal\embed\EmbedButtonInterface $button */
    $button = $this->entity;
    $form_state->setTemporaryValue('embed_button', $button);

    $form['label'] = [
      '#title' => $this->t('Label'),
      '#type' => 'textfield',
      '#default_value' => $button->label(),
      '#description' => $this->t('The human-readable name of this embed button. This text will be displayed when the user hovers over the CKEditor button. This name must be unique.'),
      '#required' => TRUE,
      '#size' => 30,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $button->id(),
      '#maxlength' => EntityTypeInterface::BUNDLE_MAX_LENGTH,
      '#disabled' => !$button->isNew(),
      '#machine_name' => [
        'exists' => ['Drupal\embed\Entity\EmbedButton', 'load'],
      ],
      '#description' => $this->t('A unique machine-readable name for this embed button. It must only contain lowercase letters, numbers, and underscores.'),
    ];

    $form['type_id'] = [
      '#type' => 'select',
      '#title' => $this->t('Embed type'),
      '#options' => $this->embedTypeManager->getDefinitionOptions(),
      '#default_value' => $button->getTypeId(),
      '#required' => TRUE,
      '#ajax' => [
        'callback' => '::updateTypeSettings',
        'effect' => 'fade',
      ],
      '#disabled' => !$button->isNew(),
    ];
    if (count($form['type_id']['#options']) == 0) {
      drupal_set_message($this->t('No embed types found.'), 'warning');
    }

    // Add the embed type plugin settings.
    $form['type_settings'] = [
      '#type' => 'container',
      '#tree' => TRUE,
      '#prefix' => '<div id="embed-type-settings-wrapper">',
      '#suffix' => '</div>',
    ];

    try {
      if ($plugin = $button->getTypePlugin()) {
        $form['type_settings'] = $plugin->buildConfigurationForm($form['type_settings'], $form_state);
      }
    }
    catch (PluginNotFoundException $exception) {
      drupal_set_message($exception->getMessage(), 'error');
      watchdog_exception('embed', $exception);
      $form['type_id']['#disabled'] = FALSE;
    }

    $config = $this->config('embed.settings');
    $upload_location = $config->get('file_scheme') . '://' . $config->get('upload_directory') . '/';
    $form['icon_file'] = [
      '#title' => $this->t('Button icon'),
      '#type' => 'managed_file',
      '#description' => $this->t('Icon for the button to be shown in CKEditor toolbar. Leave empty to use the default Entity icon.'),
      '#upload_location' => $upload_location,
      '#upload_validators' => [
        'file_validate_extensions' => ['gif png jpg jpeg'],
        'file_validate_image_resolution' => ['32x32', '16x16'],
      ],
    ];
    if ($file = $button->getIconFile()) {
      $form['icon_file']['#default_value'] = ['target_id' => $file->id()];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    /** @var \Drupal\embed\EmbedButtonInterface $button */
    $button = $this->entity;

    // Run embed type plugin validation.
    if ($plugin = $button->getTypePlugin()) {
      $plugin_form_state = clone $form_state;
      $plugin_form_state->setValues($button->getTypeSettings());
      $plugin->validateConfigurationForm($form['type_settings'], $plugin_form_state);
      if ($errors = $plugin_form_state->getErrors()) {
        foreach ($errors as $name => $error) {
          $form_state->setErrorByName($name, $error);
        }
      }
      $form_state->setValue('type_settings', $plugin_form_state->getValues());
    }
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\embed\EmbedButtonInterface $button */
    $button = $this->entity;

    // Run embed type plugin submission.
    $plugin = $button->getTypePlugin();
    $plugin_form_state = clone $form_state;
    $plugin_form_state->setValues($button->getTypeSettings());
    $plugin->submitConfigurationForm($form['type_settings'], $plugin_form_state);
    $form_state->setValue('type_settings', $plugin->getConfiguration());
    $button->set('type_settings', $plugin->getConfiguration());

    $icon_fid = $form_state->getValue(['icon_file', '0']);
    // If a file was uploaded to be used as the icon, get its UUID to be stored
    // in the config entity.
    if (!empty($icon_fid) && $file = $this->entityTypeManager->getStorage('file')->load($icon_fid)) {
      $button->set('icon_uuid', $file->uuid());
    }
    else {
      $button->set('icon_uuid', NULL);
    }

    $status = $button->save();

    $t_args = ['%label' => $button->label()];

    if ($status == SAVED_UPDATED) {
      drupal_set_message($this->t('The embed button %label has been updated.', $t_args));
    }
    elseif ($status == SAVED_NEW) {
      drupal_set_message($this->t('The embed button %label has been added.', $t_args));
      $context = array_merge($t_args, ['link' => $button->link($this->t('View'), 'collection')]);
      $this->logger('embed')->notice('Added embed button %label.', $context);
    }

    $form_state->setRedirectUrl($button->urlInfo('collection'));
  }

  /**
   * Ajax callback to update the form fields which depend on embed type.
   *
   * @param array $form
   *   The build form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   Ajax response with updated options for the embed type.
   */
  public function updateTypeSettings(array &$form, FormStateInterface $form_state) {
    $response = new AjaxResponse();

    // Update options for entity type bundles.
    $response->addCommand(new ReplaceCommand(
      '#embed-type-settings-wrapper',
      $form['type_settings']
    ));

    return $response;
  }

}
