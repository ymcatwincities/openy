<?php

namespace Drupal\ymca_mindbody\Form;

use Drupal\aggregator\Plugin\AggregatorPluginManager;
use Drupal\Component\Utility\SafeMarkup;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Core\Form\ConfigFormBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form for saving Mindbody API credentials.
 *
 * @ingroup ymca_mindbody
 */
class MindbodyCredentialsSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'mindbody_credentials_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['ymca_mindbody.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('ymca_mindbody.settings');

    $form['sourcename'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Sourcename'),
      '#default_value' => !empty($config->get('sourcename')) ? $config->get('sourcename') : '',
    );

    $form['password'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Password'),
      '#default_value' => !empty($config->get('password')) ? $config->get('password') : '',
    );

    $form['site_id'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Site ID'),
      '#default_value' => !empty($config->get('site_id')) ? $config->get('site_id') : '',
    );

    $form['user_name'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('User name'),
      '#default_value' => !empty($config->get('user_name')) ? $config->get('user_name') : '',
    );

    $form['user_password'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('User password'),
      '#default_value' => !empty($config->get('user_password')) ? $config->get('user_password') : '',
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $this->config('ymca_mindbody.settings')
      ->set('sourcename', $values['sourcename'])
      ->set('password', $values['password'])
      ->set('site_id', $values['site_id'])
      ->set('user_name', $values['user_name'])
      ->set('user_password', $values['user_password'])
      ->save();

    parent::submitForm($form, $form_state);
  }

}
