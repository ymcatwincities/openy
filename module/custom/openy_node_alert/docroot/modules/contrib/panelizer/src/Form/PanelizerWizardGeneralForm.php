<?php

namespace Drupal\panelizer\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormState;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\panelizer\PanelizerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * General settings for a panelized bundle.
 */
class PanelizerWizardGeneralForm extends FormBase {

  /**
   * The SharedTempStore key for our current wizard values.
   *
   * @var string|NULL
   */
  protected $machine_name;

  /**
   * The Panelizer service.
   *
   * @var \Drupal\panelizer\PanelizerInterface
   */
  protected $panelizer;

  /**
   * The entity type ID for the layout being worked on.
   *
   * @var string
   */
  protected $entityTypeId;

  /**
   * The entity bundle for the layout being worked on.
   *
   * @var string
   */
  protected $bundle;

  /**
   * The view mode name for the layout being worked on.
   *
   * @var string
   */
  protected $viewMode;

  /**
   * PanelizerWizardGeneralForm constructor.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match.
   * @param \Drupal\panelizer\PanelizerInterface $panelizer
   *   The Panelizer service.
   */
  public function __construct(RouteMatchInterface $route_match, PanelizerInterface $panelizer) {
    $this->routeMatch = $route_match;

    if ($route_match->getRouteName() == 'panelizer.wizard.add') {
      $this->entityTypeId = $route_match->getParameter('entity_type_id');
      $this->bundle = $route_match->getParameter('bundle');
      $this->viewMode = $route_match->getParameter('view_mode_name');
    }
    $this->panelizer = $panelizer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('current_route_match'),
      $container->get('panelizer')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'panelizer_wizard_general_form';
  }

  /**
   * @param $machine_name
   * @param $element
   */
  public static function validateMachineName($machine_name, $element) {
    // Attempt to load via the machine name and entity type.
    if (isset($element['#machine_name']['prefix'])) {
      $panelizer = \Drupal::service('panelizer');
      // Load the panels display variant.
      $full_machine_name = $element['#machine_name']['prefix'] . '__' . $machine_name;
      return $panelizer->getDefaultPanelsDisplayByMachineName($full_machine_name);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $cached_values = $form_state->getTemporaryValue('wizard');
    /** @var \Drupal\panels\Plugin\DisplayVariant\PanelsDisplayVariant $plugin */
    $plugin = $cached_values['plugin'];

    $form_state = new FormState();
    $form_state->setValues($form_state->getValue('variant_settings', []));
    $settings = $plugin->buildConfigurationForm([], $form_state);

    // If the entity view display supports custom Panelizer layouts, force use
    // of the in-place editor. Right now, there is no other way to work with
    // custom layouts.
    if (isset($cached_values['id'])) {
      list ($this->entityTypeId, $this->bundle, $this->viewMode) = explode('__', $cached_values['id']);
    }
    $panelizer_settings = $this->panelizer
      ->getPanelizerSettings($this->entityTypeId, $this->bundle, $this->viewMode);

    if (!empty($panelizer_settings['custom'])) {
      $settings['builder']['#default_value'] = 'ipe';
      $settings['builder']['#access'] = FALSE;
    }

    $settings['#tree'] = TRUE;
    $form['variant_settings'] = $settings;
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if ($form_state->hasValue('id') && !isset($this->machine_name) && $form_state->has('machine_name_prefix')) {
      $form_state->setValue('id', "{$form_state->get('machine_name_prefix')}__{$form_state->getValue('id')}");
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $cached_values = $form_state->getTemporaryValue('wizard');
    /** @var \Drupal\panels\Plugin\DisplayVariant\PanelsDisplayVariant $plugin */
    $plugin = $cached_values['plugin'];
    $plugin->submitConfigurationForm($form['variant_settings'], (new FormState())->setValues($form_state->getValue('variant_settings', [])));
    $configuration = $plugin->getConfiguration();
    $cached_values['plugin']->setConfiguration($configuration);
  }

}
