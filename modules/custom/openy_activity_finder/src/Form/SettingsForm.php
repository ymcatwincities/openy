<?php

namespace Drupal\openy_activity_finder\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Settings Form for daxko.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'openy_activity_finder_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'openy_activity_finder.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('openy_activity_finder.settings');

    $form_state->setCached(FALSE);

    $backend_options = [
      'openy_activity_finder.solr_backend' => 'Solr Backend (local db)',
    ];

    $moduleHandler = \Drupal::service('module_handler');
    if ($moduleHandler->moduleExists('openy_daxko2')){
      $backend_options['openy_daxko2.openy_activity_finder_backend'] = 'Daxko 2 (live API calls)';
    }

    $form['backend'] = [
      '#type' => 'select',
      '#options' => $backend_options,
      '#required' => TRUE,
      '#title' => $this->t('Backend for Activity Finder'),
      '#default_value' => $config->get('backend'),
      '#description' => t(''),
    ];

    $form['ages'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Ages'),
      '#default_value' => $config->get('ages'),
      '#description' => t('Ages mapping. One per line. "<number of months>,<age display label>". Example: "660,55+"'),
    ];


    $form['exclude'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Exclude category -- so we do not display Group Exercises'),
      '#default_value' => $config->get('exclude'),
      '#description' => t('Provide ID of the Program Subcategory to exclude. You do not need to provide this if you use Daxko. Needed only for Solr backend.'),
    ];

    $form['disable_search_box'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Disable Search Box'),
      '#default_value' => $config->get('disable_search_box'),
      '#description' => t('When checked hides search text box (both for Activity Finder and Results page).'),
    ];

    $form['disable_spots_available'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Disable Spots Available'),
      '#default_value' => $config->get('disable_spots_available'),
      '#description' => t('When checked disables Spots Available feature on Results page.'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    
    $config = $this->config('openy_activity_finder.settings');

    $config->set('backend', $form_state->getValue('backend'))->save();

    $config->set('ages', $form_state->getValue('ages'))->save();

    $config->set('exclude', $form_state->getValue('exclude'))->save();

    $config->set('disable_search_box', $form_state->getValue('disable_search_box'))->save();

    $config->set('disable_spots_available', $form_state->getValue('disable_spots_available'))->save();

    parent::submitForm($form, $form_state);
  }

}
