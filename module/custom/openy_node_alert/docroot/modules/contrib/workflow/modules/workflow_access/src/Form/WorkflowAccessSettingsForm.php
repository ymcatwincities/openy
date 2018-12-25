<?php

/**
 * @file
 * Contains \Drupal\workflow_access\Form\WorkflowAccessSettingsForm.
 */

namespace Drupal\workflow_access\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides the base form for workflow add and edit forms.
 */
class WorkflowAccessSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'workflow_access_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['workflow_access.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('workflow_access.settings');
    $weight = $config->get('workflow_access_priority');

    $form['workflow_access'] = array(
      '#type' => 'details',
      '#open' => TRUE,
      '#title' => t('Workflow Access Settings'),
    );
    $form['workflow_access']['#tree'] = TRUE;

    $url = 'https://api.drupal.org/api/drupal/core%21modules%21node%21node.api.php/function/hook_node_access_records/8';
    $form['workflow_access']['workflow_access_priority'] = array(
      '#type' => 'weight',
      '#delta' => 10,
      '#title' => t('Workflow Access Priority'),
      '#default_value' => $weight,
      '#description' => t('This sets the node access priority. Changing this
      setting can be dangerous. If there is any doubt, leave it at 0.
      <a href=":url" target="_blank">Read the manual</a>.', array(':url' => $url)),
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $weight = $form_state->getValues()['workflow_access']['workflow_access_priority'];

    $this->config('workflow_access.settings')
      ->set('workflow_access_priority', $weight)
      ->save();

    parent::submitForm($form, $form_state);
  }

}
