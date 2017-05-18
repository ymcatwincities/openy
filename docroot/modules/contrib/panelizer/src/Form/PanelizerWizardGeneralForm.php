<?php

namespace Drupal\panelizer\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormState;
use Drupal\Core\Form\FormStateInterface;

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
  public function buildForm(array $form, FormStateInterface $form_state, $machine_name = NULL) {
    $this->machine_name = $machine_name;
    $cached_values = $form_state->getTemporaryValue('wizard');
    /** @var \Drupal\panels\Plugin\DisplayVariant\PanelsDisplayVariant $plugin */
    $plugin = $cached_values['plugin'];
    $form['variant_settings'] = $plugin->buildConfigurationForm([], (new FormState())->setValues($form_state->getValue('variant_settings', [])));
    $form['variant_settings']['#tree'] = TRUE;
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
