<?php

namespace Drupal\activenet\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Settings Form for activenet.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'activenet_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'activenet.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('activenet.settings');

    $form_state->setCached(FALSE);

    $form['base_uri'] = [
      '#type' => 'url',
      '#title' => $this->t('ActiveNet API Base URI'),
      '#default_value' => $config->get('base_uri'),
      '#description' => t('Add your ActiveNet API base uri here. It follows the format of https://{host address}/{service name}/{organization id}/api/{API version}/. For information on bulding your Base URI see https://help.aw.active.com/ActiveNet/standard/en_US/ActiveNetHelp.htm#api_Retrieving_data_from_ACTIVE_Net.htm'),
    ];

    $form['api_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('ActiveNet key'),
      '#default_value' => $config->get('api_key'),
      '#description' => t('Add your ActiveNet API key. Will be a long string provided by Active Net support team, similiar to 1234567890xn3xnteudxsavw.'),
    ];    

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    /* @var $config \Drupal\Core\Config\Config */
    $config = $this->configFactory->getEditable('activenet.settings');
    
    $config->set('api_key', $form_state->getValue('api_key'))->save();
    if ($base_uri = $form_state->getValue('base_uri')) {
      if (preg_match("#https?://#", $base_uri) === 0) {
        $base_uri = 'https://' . $base_uri;
      }
      $config->set('base_uri', rtrim($base_uri, '/') . '/')->save();
    }

    parent::submitForm($form, $form_state);
  }

}
