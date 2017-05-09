<?php

namespace Drupal\embedded_groupexpro_schedule\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure account settings form.
 */
class EmbeddedGroupExProSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'embeddedgroupexpro_account_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'embeddedgroupexpro.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('embeddedgroupexpro.settings');

    $form['account'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('GroupEx Pro Account'),
      '#default_value' => $config->get('account'),
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Retrieve the configuration.
    $this->config('embeddedgroupexpro.settings')->set('account', $form_state->getValue('account'))->save();

    parent::submitForm($form, $form_state);
  }

}
