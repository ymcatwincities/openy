<?php

namespace Drupal\embedded_groupexpro_schedule\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;

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
      'embedded_groupexpro_schedule.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('embedded_groupexpro_schedule.settings');

    $url = Url::fromUri('https://www.groupexpro.com/');
    $link = Link::fromTextAndUrl('GroupEx Pro', $url);

    $form['account'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('GroupEx Pro Account'),
      '#default_value' => $config->get('account'),
      '#description' => t('Add your @link account id here. It is most likely a short number, like 123.', ['@link' => $link->toString()]),
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Retrieve the configuration.
    $this->config('embedded_groupexpro_schedule.settings')->set('account', $form_state->getValue('account'))->save();

    parent::submitForm($form, $form_state);
  }

}
