<?php

namespace Drupal\mindbody_cache_proxy\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

/**
 * Settings form.
 */
class MindbodyCacheProxySettingsForm extends ConfigFormBase implements ContainerAwareInterface {

  use ContainerAwareTrait;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'mindbody_cache_proxy_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $state = $this->container->get('state');
    $stats = $state->get('mindbody_cache_proxy');
    $config = $this->config('mindbody_cache_proxy.settings');

    $form['settings'] = [
      '#title' => $this->t('Settings'),
      '#type' => 'fieldset',
    ];

    $primary = $config->get('primary') ?: FALSE;
    $form['settings']['primary'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('This is a primary endpoint'),
      '#default_value' => $primary,
      '#description' => $this->t('Only primary endpoints can break calls limit.'),
    );

    $calls = $config->get('calls') ?: 0;
    $form['settings']['calls'] = [
      '#title' => $this->t('Calls available'),
      '#type' => 'textfield',
      '#description' => $this->t('Free calls available within MindBody agreement.'),
      '#default_value' => $calls,
    ];

    $endpoint = $config->get('endpoint') ?: '';
    $form['settings']['endpoint'] = [
      '#title' => $this->t('Primary endpoint'),
      '#type' => 'textfield',
      '#description' => $this->t('Leave empty if this endpoint is primary. Example: http://example.com/mindbody/status.'),
      '#default_value' => $endpoint,
    ];

    $token = $config->get('token') ?: '';
    $form['settings']['token'] = [
      '#title' => $this->t('Access Token'),
      '#type' => 'textfield',
      '#description' => $this->t('Tokens should match on primary and secondary endpoints.'),
      '#default_value' => $token,
      '#required' => TRUE,
    ];

    $form['settings']['submit'] = [
      '#name' => 'submit',
      '#type' => 'submit',
      '#value' => $this->t('Save'),
    ];

    $form['stats'] = [
      '#title' => $this->t('Cache statistics'),
      '#type' => 'fieldset',
    ];

    $message = 'Since %date (UTC) %calls API calls and %hits cache hits. Calls remaining: %remain.';

    $date_time = new \DateTime();
    $date_time->setTimestamp($stats->timestamp);
    $date_time->setTimezone(new \DateTimeZone('UTC'));

    $form['stats']['requests'] = [
      '#markup' => $this->t(
        $message,
        [
          '%date' => $date_time->format('Y-m-d'),
          '%calls' => $stats->miss,
          '%hits' => $stats->hit,
          '%remain' => $calls - $stats->miss,
        ]
      ),
    ];

    $form['actions'] = [
      '#title' => $this->t('Actions'),
      '#type' => 'fieldset',
    ];

    $form['actions']['reset'] = [
      '#name' => 'reset',
      '#type' => 'submit',
      '#value' => $this->t('Clear the cache'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['mindbody_cache_proxy.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    $form_state->cleanValues();
    $triggering_element = $form_state->getTriggeringElement();

    if ($triggering_element['#name'] == 'submit') {
      if (empty($form_state->getValue('primary')) && empty($form_state->getValue('endpoint'))) {
        $form_state->setError($form['settings']['endpoint'], $this->t('Please, provide primary endpoint.'));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Remove button and internal Form API values from submitted values.
    $form_state->cleanValues();
    $triggering_element = $form_state->getTriggeringElement();

    switch ($triggering_element['#name']) {
      case 'reset':
        $manager = $this->container->get('mindbody_cache_proxy.manager');
        $manager->resetCache();
        break;

      case 'submit':
        $data = [
          'calls' => $form_state->getValue('calls'),
          'endpoint' => $form_state->getValue('endpoint'),
          'primary' => $form_state->getValue('primary'),
          'token' => $form_state->getValue('token'),
        ];
        $this->config('mindbody_cache_proxy.settings')->setData($data)->save();
        break;
    }

    parent::submitForm($form, $form_state);
  }

}
