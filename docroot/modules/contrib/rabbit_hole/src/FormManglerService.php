<?php

namespace Drupal\rabbit_hole;

use Drupal\Core\Entity\Entity;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityTypeBundleInfo;
use Drupal\rabbit_hole\Plugin\RabbitHoleBehaviorPluginManager;
use Drupal\rabbit_hole\Plugin\RabbitHoleEntityPluginManager;
use Drupal\rabbit_hole\Entity\BehaviorSettings;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class FormManglerService.
 *
 * @package Drupal\rabbit_hole
 */
class FormManglerService {
  use StringTranslationTrait;

  const RABBIT_HOLE_USE_DEFAULT = 'bundle_default';

  private $entityTypeManager = NULL;
  private $bundleInfo = NULL;
  private $rhBehaviorPluginManager = NULL;
  private $rhEntityPluginManager = NULL;

  /**
   * Constructor.
   */
  public function __construct(
    EntityTypeManagerInterface $etm,
    EntityTypeBundleInfo $etbi,
    RabbitHoleBehaviorPluginManager $behavior_plugin_manager,
    RabbitHoleEntityPluginManager $entity_plugin_manager,
    BehaviorSettingsManager $behavior_settings_manager,
    TranslationInterface $translation) {

    $this->entityTypeManager = $etm;
    $this->allBundleInfo = $etbi->getAllBundleInfo();
    $this->rhBehaviorPluginManager = $behavior_plugin_manager;
    $this->rhEntityPluginManager = $entity_plugin_manager;
    $this->rhBehaviorSettingsManager = $behavior_settings_manager;
    $this->stringTranslation = $translation;
  }

  /**
   * Add rabbit hole options to an entity type's global configuration form.
   *
   * (E.g. options for all users).
   *
   * @param array $attach
   *   The form that the Rabbit Hole form should be attached to.
   * @param string $entity_type
   *   The name of the entity for which this form provides global options.
   */
  public function addRabbitHoleOptionsToGlobalForm(array &$attach, $entity_type, FormStateInterface $form_state, $form_id) {
    $entity_type = $this->entityTypeManager->getStorage($entity_type)
      ->getEntityType();

    $this->addRabbitHoleOptionsToForm($attach, $entity_type->id(), NULL,
      $form_state, $form_id);
  }

  /**
   * Form structure for the Rabbit Hole configuration.
   *
   * This should be used by other modules that wish to implement the Rabbit Hole
   * configurations in any form.
   *
   * @param array $attach
   *   The form that the Rabbit Hole form should be attached to.
   * @param \Drupal\Core\Entity\Entity $entity
   *   The entity that we're adding the form to, e.g. a node.  This should be
   *    defined even in the case of bundles since it is used to determine bundle
   *    and entity type.
   */
  public function addRabbitHoleOptionsToEntityForm(array &$attach, Entity $entity, FormStateInterface $form_state, $form_id) {
    $this->addRabbitHoleOptionsToForm($attach, $entity->getEntityType()->id(),
      $entity, $form_state, $form_id);
  }

  /**
   * Common functionality for adding rabbit hole options to forms.
   *
   * @param array $attach
   *   The form that the Rabbit Hole form should be attached to.
   * @param string $entity_type_id
   *   The string ID of the entity type for the form, e.g. 'node'.
   * @param object $entity
   *   The entity that we're adding the form to, e.g. a node.  This should be
   *    defined even in the case of bundles since it is used to determine bundle
   *    and entity type.
   */
  private function addRabbitHoleOptionsToForm(array &$attach, $entity_type_id,
    $entity, FormStateInterface $form_state, $form_id) {

    $entity_type = $this->entityTypeManager->getStorage($entity_type_id)
      ->getEntityType();

    if ($entity === NULL) {
      $is_bundle_or_entity_type = TRUE;
    }
    else {
      $is_bundle_or_entity_type = $this->isEntityBundle($entity);
    }

    $bundle_settings = NULL;
    $bundle = isset($entity) ? $entity->bundle() : $entity_type_id;
    $action = NULL;

    if ($is_bundle_or_entity_type) {
      if ($entity === NULL) {
        $bundle_settings = $this->rhBehaviorSettingsManager
          ->loadBehaviorSettingsAsConfig($entity_type->id());
      }
      else {
        $bundle_settings = $this->rhBehaviorSettingsManager
          ->loadBehaviorSettingsAsConfig($entity_type->id(), $entity->id());
      }

      $action = $bundle_settings->get('action');
    }
    else {
      $bundle_entity_type = $entity_type->getBundleEntityType()
        ?: $entity_type->id();
      $bundle_settings = $this->rhBehaviorSettingsManager
        ->loadBehaviorSettingsAsConfig($bundle_entity_type,
          $entity->getEntityType()->getBundleEntityType()
            ? $entity->bundle() : NULL);

      // If the form is about to be attached to an entity,
      // but the bundle isn't allowed to be overridden, exit.
      if (!$bundle_settings->get('allow_override')) {
        return;
      }

      $action = isset($entity->rh_action->value)
        ? $entity->rh_action->value
        : 'bundle_default';
    }

    $is_bundle = $this->isEntityBundle($entity);
    $entity_plugin = $this->rhEntityPluginManager->createInstanceByEntityType(
      $is_bundle_or_entity_type && !empty($entity_type->getBundleOf())
        ? $entity_type->getBundleOf() : $entity_type->id());

    // If the user doesn't have access, exit.
    // Get information about the entity.
    // TODO: Should be possible to get this as plural? Look into this.
    $entity_label = $entity_type->getLabel();

    $bundle_info = isset($this->allBundleInfo[$entity_type->id()])
      ? $this->allBundleInfo[$entity_type->id()] : NULL;

    // Get the label for the bundle. This won't be set when the user is creating
    // a new bundle. In that case, fallback to "this bundle".
    $bundle_label = NULL !== $bundle_info && NULL !== $bundle_info[$bundle]['label']
      ? $bundle_info[$bundle]['label'] : $this->t('this bundle');

    // Wrap everything in a fieldset.
    $form['rabbit_hole'] = array(
      '#type' => 'details',
      '#title' => $this->t('Rabbit Hole settings'),
      '#collapsed' => FALSE,
      '#collapsible' => TRUE,

      // TODO: Should probably handle group in a plugin - not sure if, e.g.,
      // files will work in the same way and even if they do later entities
      // might not.
      '#group' => $is_bundle_or_entity_type ? 'additional_settings' : 'advanced',
      '#attributes' => array('class' => array('rabbit-hole-settings-form')),
    );

    // Add the invoking module to the internal values.
    // TODO: This can probably be removed - check.
    $form['rabbit_hole']['rh_is_bundle'] = array(
      '#type' => 'hidden',
      '#value' => $is_bundle_or_entity_type,
    );

    $form['rabbit_hole']['rh_entity_type'] = array(
      '#type' => 'hidden',
      '#value' => $entity_type->id(),
    );

    // Add override setting if we're editing a bundle.
    if ($is_bundle_or_entity_type) {
      $form['rabbit_hole']['rh_override'] = array(
        '#type' => 'checkbox',
        '#title' => t('Allow these settings to be overridden for individual entities'),
        '#default_value' => $bundle_settings->get('allow_override'),
        '#description' => t('If this is checked, users with the %permission permission will be able to override these settings for individual entities.', array('%permission' => t('Administer Rabbit Hole settings for @entity_type', array('@entity_type' => $entity_label)))),
      );
    }

    // Add action setting.
    $action_options = $this->loadBehaviorOptions();

    if (!$is_bundle_or_entity_type) {
      // Add an option if we are editing an entity. This will allow us to use
      // the configuration for the bundle.
      $action_bundle = $bundle_settings->get('action');
      $action_options = array(
        self::RABBIT_HOLE_USE_DEFAULT => t('Global @bundle behavior (@setting)', array('@bundle' => strtolower($bundle_label), '@setting' => $action_options[$action_bundle])),
      ) + $action_options;
    }

    $form['rabbit_hole']['rh_action'] = array(
      '#type' => 'radios',
      '#title' => $this->t('Behavior'),
      '#options' => $action_options,
      '#default_value' => $action,
      '#description' => $this->t('What should happen when someone tries to visit an entity page for @bundle?', array('@bundle' => strtolower($bundle_label))),
      '#attributes' => array('class' => array('rabbit-hole-action-setting')),
    );

    $this->populateExtraBehaviorSections($form, $form_state, $form_id, $entity,
      $is_bundle_or_entity_type, $bundle_settings);

    // Attach the Rabbit Hole form to the main form, and add a custom validation
    // callback.
    $attach += $form;

    // TODO: Optionally provide a form validation handler (can we do this via
    // plugin?).
    //
    // If the implementing module provides a submit function for the bundle
    // form, we'll add it as a submit function for the attached form. We'll also
    // make sure that this won't be added for entity forms.
    //
    // TODO: This should probably be moved out into plugins based on entity
    // type.
    $is_global_form = isset($attach['#form_id'])
      && $attach['#form_id'] === $entity_plugin->getGlobalConfigFormId();
    $submit_handler_locations = $is_global_form
      ? $entity_plugin->getGlobalFormSubmitHandlerAttachLocations()
      : $entity_plugin->getFormSubmitHandlerAttachLocations();

    foreach ($submit_handler_locations as $location) {
      $array_ref = &$attach;
      if (is_array($location)) {
        foreach ($location as $subkey) {
          $array_ref = &$array_ref[$subkey];
        }
      }
      else {
        $array_ref = &$array_ref[$location];
      }
      $array_ref[] = '_rabbit_hole_general_form_submit';
    }

    // TODO: Optionally provide additional form submission handler (can we do
    // this via plugin?).
  }

  /**
   * Handle general aspects of rabbit hole form submission.
   *
   * (Not specific to node etc.).
   *
   * @param array $form
   *   The form.
   * @param string|int|object $form_state
   *   The form state.
   */
  public function handleFormSubmit($form, $form_state) {
    if ($form_state->getValue('rh_is_bundle')) {
      $entity = NULL;
      if (method_exists($form_state->getFormObject(), 'getEntity')) {
        $entity = $form_state->getFormObject()->getEntity();
      }
      $allow_override = $form_state->getValue('rh_override')
        ? BehaviorSettings::OVERRIDE_ALLOW
        : BehaviorSettings::OVERRIDE_DISALLOW;

      $this->rhBehaviorSettingsManager->saveBehaviorSettings(
        array(
          'action' => $form_state->getValue('rh_action'),
          'allow_override' => $allow_override,
          'redirect' => $form_state->getValue('rh_redirect')
          ?: '',
          'redirect_code' => $form_state->getValue('rh_redirect_response')
          ?: BehaviorSettings::REDIRECT_NOT_APPLICABLE,
        ),
        $form_state->getValue('rh_entity_type'),
        isset($entity) ? $entity->id() : NULL
      );
    }
  }

  /**
   * Load an array of behaviour options from plugins.
   *
   * Load an array of rabbit hole behavior options from plugins in the format
   * option id => label.
   *
   * @return array
   *   An array of behavior options
   */
  protected function loadBehaviorOptions() {
    $action_options = array();
    foreach ($this->rhBehaviorPluginManager->getDefinitions() as $id => $def) {
      $action_options[$id] = $def['label'];
    }
    return $action_options;
  }

  /**
   * Add additional fields to the form based on behaviors.
   *
   * @param array $form
   *   The form array.
   * @param array $form_state
   *   The form state.
   * @param string $form_id
   *   The form ID.
   * @param \Drupal\Core\Entity\Entity|NULL $entity
   *   The entity whose settings form we are displaying.
   * @param bool $entity_is_bundle
   *   Whether the entity is a bundle.
   * @param \Drupal\Core\Config\ImmutableConfig|NULL $bundle_settings
   *   The settings for this bundle.
   */
  protected function populateExtraBehaviorSections(&$form, $form_state,
    $form_id, Entity $entity = NULL, $entity_is_bundle = FALSE,
    ImmutableConfig $bundle_settings = NULL) {

    foreach ($this->rhBehaviorPluginManager->getDefinitions() as $id => $def) {
      $this->rhBehaviorPluginManager
        ->createInstance($id)
        ->settingsForm($form['rabbit_hole'], $form_state, $form_id, $entity,
          $entity_is_bundle, $bundle_settings);
    }
  }

  /**
   * TODO.
   */
  protected function isEntityBundle($entity) {
    return is_subclass_of($entity,
      'Drupal\Core\Config\Entity\ConfigEntityBundleBase');
  }

}
