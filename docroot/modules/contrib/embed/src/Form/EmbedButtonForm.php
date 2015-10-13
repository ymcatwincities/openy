<?php

/**
 * @file
 * Contains \Drupal\embed\Form\EmbedButtonForm.
 */

namespace Drupal\embed\Form;

use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\ckeditor\CKEditorPluginManager;
use Drupal\embed\EmbedType\EmbedTypeManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form controller for embed button forms.
 */
class EmbedButtonForm extends EntityForm {

  /**
   * The entity manager service.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * The embed type plugin manager.
   *
   * @var \Drupal\embed\EmbedType\EmbedTypeManager
   */
  protected $embedTypeManager;

  /**
   * The CKEditor plugin manager.
   *
   * @var \Drupal\ckeditor\CKEditorPluginManager
   */
  protected $ckeditorPluginManager;

  /**
   * Constructs a new EmbedButtonForm.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager service.
   * @param \Drupal\embed\EmbedType\EmbedTypeManager $embed_type_manager
   *   The embed type plugin manager.
   * @param \Drupal\ckeditor\CKEditorPluginManager $ckeditor_plugin_manager
   *   The CKEditor plugin manager.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   */
  public function __construct(EntityManagerInterface $entity_manager, EmbedTypeManager $embed_type_manager, CKEditorPluginManager $ckeditor_plugin_manager, ConfigFactoryInterface $config_factory) {
    $this->entityManager = $entity_manager;
    $this->embedTypeManager = $embed_type_manager;
    $this->ckeditorPluginManager = $ckeditor_plugin_manager;
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager'),
      $container->get('plugin.manager.embed.type'),
      $container->get('plugin.manager.ckeditor.plugin'),
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

    $form['label'] = array(
      '#title' => t('Label'),
      '#type' => 'textfield',
      '#default_value' => $button->label(),
      '#description' => t('The human-readable name of this embed button. This text will be displayed when the user hovers over the CKEditor button. This name must be unique.'),
      '#required' => TRUE,
      '#size' => 30,
    );

    $form['id'] = array(
      '#type' => 'machine_name',
      '#default_value' => $button->id(),
      '#maxlength' => EntityTypeInterface::BUNDLE_MAX_LENGTH,
      '#disabled' => !$button->isNew(),
      '#machine_name' => array(
        'exists' => ['Drupal\embed\Entity\EmbedButton', 'load'],
      ),
      '#description' => t('A unique machine-readable name for this embed button. It must only contain lowercase letters, numbers, and underscores.'),
    );

    $form['type_id'] = array(
      '#type' => 'select',
      '#title' => $this->t('Embed provider'),
      '#options' => $this->embedTypeManager->getDefinitionOptions(),
      '#default_value' => $button->getTypeId(),
      '#description' => $this->t("Embed type for which this button is to enabled."),
      '#required' => TRUE,
      '#ajax' => array(
        'callback' => '::updateTypeSettings',
        'effect' => 'fade',
      ),
      '#disabled' => !$button->isNew(),
    );
    if (count($form['type_id']['#options']) == 0) {
      drupal_set_message($this->t('No embed type providers found.'), 'warning');
    }

    // Add the embed type plugin settings.
    $form['type_settings'] = array(
      '#type' => 'container',
      '#tree' => TRUE,
      '#prefix' => '<div id="embed-type-settings-wrapper">',
      '#suffix' => '</div>',
    );

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
    $form['icon_file'] = array(
      '#title' => $this->t('Button icon image'),
      '#type' => 'managed_file',
      '#description' => $this->t("Image for the button to be shown in CKEditor toolbar. Leave empty to use the default Entity icon."),
      '#upload_location' => $upload_location,
      '#upload_validators' => array(
        'file_validate_extensions' => array('gif png jpg jpeg'),
        'file_validate_image_resolution' => array('32x32', '16x16'),
      ),
    );
    if ($file = $button->getIconFile()) {
      $form['icon_file']['#default_value'] = array('target_id' => $file->id());
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

    if ($button->isNew()) {
      // Get a list of all buttons that are provided by all plugins.
      $all_buttons = array_reduce($this->ckeditorPluginManager->getButtons(), function($result, $item) {
        return array_merge($result, array_keys($item));
      }, array());
      // Ensure that button ID is unique.
      if (in_array($button->id(), $all_buttons)) {
        $form_state->setErrorByName('id', $this->t('A CKEditor button with ID %id already exists.', array('%id' => $button->id())));
      }
    }

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

    $icon_fid = $form_state->getValue(array('icon_file', '0'));
    // If a file was uploaded to be used as the icon, get its UUID to be stored
    // in the config entity.
    if (!empty($icon_fid) && $file = $this->entityManager->getStorage('file')->load($icon_fid)) {
      $button->set('icon_uuid', $file->uuid());
    }
    else {
      $button->set('icon_uuid', NULL);
    }

    $status = $button->save();

    $t_args = array('%label' => $button->label());

    if ($status == SAVED_UPDATED) {
      drupal_set_message(t('The embed button %label has been updated.', $t_args));
    }
    elseif ($status == SAVED_NEW) {
      drupal_set_message(t('The embed button %label has been added.', $t_args));
      $context = array_merge($t_args, array('link' => $button->link($this->t('View'), 'collection')));
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
   * @return AjaxResponse
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
