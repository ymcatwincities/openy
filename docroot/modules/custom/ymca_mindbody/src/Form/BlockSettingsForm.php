<?php

namespace Drupal\ymca_mindbody\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\ConfigFormBase;

/**
 * Provides MindBody block settings form.
 */
class BlockSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'ymca_mindbody_block_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['ymca_mindbody.block.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('ymca_mindbody.block.settings');

    $fields = [
      'disabled_form_block_id' => 'Disabled form Block ID',
    ];

    foreach ($fields as $field => $title) {
      $form[$field] = array(
        '#type' => 'textfield',
        '#title' => $this->t($title),
        '#default_value' => !empty($config->get($field)) ? $config->get($field) : '',
      );
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $this->config('ymca_mindbody.block.settings')
      ->set('disabled_form_block_id', $values['disabled_form_block_id'])
      ->save();

    parent::submitForm($form, $form_state);
  }

}
