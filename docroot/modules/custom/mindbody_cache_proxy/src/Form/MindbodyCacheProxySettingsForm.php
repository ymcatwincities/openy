<?php

namespace Drupal\mindbody_cache_proxy\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\State\StateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Settings form.
 */
class MindbodyCacheProxySettingsForm extends FormBase {

  /**
   * State.
   *
   * @var StateInterface
   */
  protected $state;

  /**
   * MindbodyCacheProxySettingsForm constructor.
   *
   * @param StateInterface $state
   *   State.
   */
  public function __construct(StateInterface $state) {
    $this->state = $state;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('state'));
  }

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

    $stats = $this->state->get('mindbody_cache_proxy');

    $form['stats'] = array(
      '#title' => t('Cache statistics'),
      '#type' => 'fieldset',
    );

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

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
  }

}
