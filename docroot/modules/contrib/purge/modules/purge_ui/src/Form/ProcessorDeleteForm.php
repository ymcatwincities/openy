<?php

namespace Drupal\purge_ui\Form;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\CloseModalDialogCommand;
use Drupal\purge\Plugin\Purge\Processor\ProcessorsServiceInterface;
use Drupal\purge_ui\Form\CloseDialogTrait;
use Drupal\purge_ui\Form\ReloadConfigFormCommand;

/**
 * Delete processor {id}.
 */
class ProcessorDeleteForm extends ConfirmFormBase {
  use CloseDialogTrait;

  /**
   * @var \Drupal\purge\Plugin\Purge\Processor\ProcessorsServiceInterface
   */
  protected $purgeProcessors;

  /**
   * The processor object to be deleted.
   *
   * @var \Drupal\purge\Plugin\Purge\Processor\ProcessorInterface
   */
  protected $processor;

  /**
   * Constructs a ProcessorDeleteForm object.
   *
   * @param \Drupal\purge\Plugin\Purge\Processor\ProcessorsServiceInterface $purge_processors
   *   The purge processors service.
   *
   * @return void
   */
  public function __construct(ProcessorsServiceInterface $purge_processors) {
    $this->purgeProcessors = $purge_processors;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('purge.processors'));
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'purge_ui.processor_delete_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $this->processor = $this->purgeProcessors->get($form_state->getBuildInfo()['args'][0]);
    $form = parent::buildForm($form, $form_state);

    // This is rendered as a modal dialog, so we need to set some extras.
    $form['#attached']['library'][] = 'core/drupal.dialog.ajax';

    // Update the buttons and bind callbacks.
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#button_type' => 'primary',
      '#value' => $this->getConfirmText(),
      '#ajax' => ['callback' => '::deleteProcessor'],
    ];
    $form['actions']['cancel'] = [
      '#type' => 'submit',
      '#value' => $this->t('No'),
      '#weight' => -10,
      '#ajax' => ['callback' => '::closeDialog'],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Yes, delete this processor!');
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete the @label processor?', ['@label' => $this->processor->getLabel()]);
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {}

  /**
   * Delete the processor.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   */
  public function deleteProcessor(array &$form, FormStateInterface $form_state) {
    $response = new AjaxResponse();
    $enabled = $this->purgeProcessors->getPluginsEnabled();
    $id = $form_state->getBuildInfo()['args'][0];
    if (in_array($id, $enabled)) {
      foreach ($enabled as $i => $plugin_id) {
        if ($id === $plugin_id) {
          unset($enabled[$i]);
        }
      }
      $this->purgeProcessors->setPluginsEnabled($enabled);
      $response->addCommand(new ReloadConfigFormCommand('edit-queue'));
    }
    $response->addCommand(new CloseModalDialogCommand());
    return $response;
  }

}
