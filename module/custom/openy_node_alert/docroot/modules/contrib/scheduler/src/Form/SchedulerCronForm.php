<?php

namespace Drupal\scheduler\Form;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\scheduler\SchedulerManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Scheduler Lightweight Cron form.
 */
class SchedulerCronForm extends ConfigFormBase {

  /**
   * The module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The scheduler manager service.
   *
   * @var Drupal\scheduler\SchedulerManager
   */
  protected $schedulerManager;

  /**
   * Creates a SchedulerCronForm instance.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler service.
   * @var Drupal\scheduler\SchedulerManager $scheduler_manager
   *   The scheduler manager service.
   */
  public function __construct(ModuleHandlerInterface $module_handler, SchedulerManager $scheduler_manager) {
    $this->moduleHandler = $module_handler;
    $this->schedulerManager = $scheduler_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('module_handler'), $container->get('scheduler.manager'));
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'scheduler_cron_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['scheduler.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('scheduler.settings');

    $form['cron_settings'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Lightweight cron settings'),
    ];
    $form['cron_settings']['lightweight_log'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Log every activation and completion message.'),
      '#default_value' => $config->get('log'),
      '#description' => $this->t('When this option is checked, Scheduler will write an entry to the log every time the lightweight cron process is started and completed. This is useful during set up and testing, but can result in a large number of log entries. Any actions performed during the lightweight cron run will always be logged regardless of this setting.'),
    ];
    $form['cron_settings']['lightweight_access_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Lightweight cron access key'),
      '#default_value' => $config->get('lightweight_cron_access_key'),
      '#required' => TRUE,
      '#size' => 25,
      '#description' => $this->t("Similar to Drupal's cron key this acts as a security token to prevent unauthorised calls to scheduler/cron. The key should be passed as scheduler/cron/{access key}"),
    ];
    // Add a submit handler function for the key generation.
    $form['cron_settings']['create_key'][] = [
      '#type' => 'submit',
      '#value' => $this->t('Generate new random key'),
      '#submit' => ['::generateRandomKey'],
      // No validation at all is required in the equivocate case, so
      // we include this here to make it skip the form-level validator.
      '#validate' => [],
    ];
    // Add a submit handler function for the form.
    $form['buttons']['submit_cron'][] = [
      '#type' => 'submit',
      '#prefix' => $this->t("You can test Scheduler's lightweight cron process interactively"),
      '#value' => $this->t("Run Scheduler's lightweight cron now"),
      '#submit' => ['::runLightweightCron'],
      // No validation at all is required in the equivocate case, so
      // we include this here to make it skip the form-level validator.
      '#validate' => [],
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('scheduler.settings');
    $config->set('log', $form_state->getValue('lightweight_log'));
    $config->set('lightweight_cron_access_key', $form_state->getValue('lightweight_access_key'));
    $config->save();
    parent::submitForm($form, $form_state);
  }

  /**
   * Form submission handler for the random key generation.
   *
   * This only fires when the 'Generate new random key' button is clicked.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function generateRandomKey(array &$form, FormStateInterface $form_state) {
    $config = $this->config('scheduler.settings');
    $config->set('lightweight_cron_access_key', substr(md5(rand()), 0, 20));
    $config->save();
    parent::submitForm($form, $form_state);
  }

  /**
   * Form submission handler to run the lightweight cron.
   *
   * This only fires when "Run Scheduler's lightweight cron now" is clicked.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function runLightweightCron(array &$form, FormStateInterface $form_state) {
    $this->schedulerManager->runLightweightCron();

    if ($this->moduleHandler->moduleExists('dblog')) {
      $url = Url::fromRoute('dblog.overview')->toString();
      $message = $this->t('Lightweight cron run completed. See the <a href="@url">log</a> for details.', ['@url' => $url]);
    }
    else {
      // If the Database Logging module is not enabled the route to the log
      // overview does not exist. Show a simple status message.
      $message = $this->t('Lightweight cron run completed.');
    }
    // @todo Replace drupal_set_message() with an injectable service in 8.1.x.
    // @see https://www.drupal.org/node/2278383
    drupal_set_message($message);
  }

}
