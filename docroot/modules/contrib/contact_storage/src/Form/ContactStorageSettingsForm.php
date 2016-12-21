<?php

namespace Drupal\contact_storage\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Defines a class for contact_storage's settings form.
 */
class ContactStorageSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'contact_storage_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'contact_storage.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('contact_storage.settings');

    // Global setting.
    $form['send_html'] = array(
      '#type' => 'checkbox',
      '#title' => t('Send HTML'),
      '#description' => t('Whether the mails should be sent as HTML. A module like <a href="https://www.drupal.org/project/swiftmailer">Swiftmailer</a> is needed for this feature. This has only been tested with the Swiftmailer module, other modules might not work out of the box and will not use the provided default template.'),
      '#default_value' => $config->get('send_html'),
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $this->config('contact_storage.settings')
      ->set('send_html', $form_state->getValue('send_html'))
      ->save();
  }

}
