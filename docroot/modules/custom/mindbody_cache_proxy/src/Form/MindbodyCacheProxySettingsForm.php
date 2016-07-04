<?php

namespace Drupal\mindbody_cache_proxy\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\mindbody_cache_proxy\Entity\MindbodyCache;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

/**
 * Settings form.
 */
class MindbodyCacheProxySettingsForm extends FormBase implements ContainerAwareInterface {

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

    $form['stats'] = [
      '#title' => t('Cache statistics'),
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
          '%remain' => 1000 - $stats->miss,
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

    return $form;
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
    }
  }

}
