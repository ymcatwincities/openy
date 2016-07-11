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
    $calls = !empty($config->get('calls')) ? $config->get('calls') : 1000;

    $form['settings'] = [
      '#title' => $this->t('Settings'),
      '#type' => 'fieldset',
    ];

    $form['settings']['calls'] = [
      '#title' => $this->t('Calls available'),
      '#type' => 'textfield',
      '#description' => $this->t('Free calls available within MindBody agreement.'),
      '#default_value' => $calls,
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
        $this->config('mindbody_cache_proxy.settings')
          ->set('calls', $form_state->getValue('calls'))
          ->save();
        break;
    }

    parent::submitForm($form, $form_state);
  }

}
