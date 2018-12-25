<?php

namespace Drupal\purge_ui\Form;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\CloseModalDialogCommand;
use Drupal\purge\Plugin\Purge\Queuer\QueuersServiceInterface;
use Drupal\purge_ui\Form\CloseDialogTrait;
use Drupal\purge_ui\Form\ReloadConfigFormCommand;

/**
 * Add a queuer.
 */
class QueuerAddForm extends ConfigFormBase {
  use CloseDialogTrait;

  /**
   * @var \Drupal\purge\Plugin\Purge\Queuer\QueuersServiceInterface
   */
  protected $purgeQueuers;

  /**
   * Constructs a QueuerAddForm object.
   *
   * @param \Drupal\purge\Plugin\Purge\Queuer\QueuersServiceInterface $purge_queuers
   *   The purge queuers service.
   *
   * @return void
   */
  public function __construct(QueuersServiceInterface $purge_queuers) {
    $this->purgeQueuers = $purge_queuers;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('purge.queuers'));
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
    return 'purge_ui.queuer_add_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $definitions = $this->purgeQueuers->getPlugins();
    $form = parent::buildForm($form, $form_state);
    $form['#attached']['library'][] = 'core/drupal.dialog.ajax';

    // List all available queuers.
    $options = [];
    foreach ($this->purgeQueuers->getPluginsAvailable() as $plugin_id) {
      $options[$plugin_id] = t("@label: @description", [
        '@label' => $definitions[$plugin_id]['label'],
        '@description' => $definitions[$plugin_id]['description'],
      ]);
    }
    $form['id'] = [
      '#default_value' => count($options) ? key($options) : NULL,
      '#type' => 'radios',
      '#options' => $options,
    ];

    // Update the buttons and bind callbacks.
    $form['actions']['submit'] = [
      '#access' => count($options),
      '#type' => 'submit',
      '#button_type' => 'primary',
      '#value' => $this->t("Add"),
      '#ajax' => ['callback' => '::addQueuer'],
    ];
    $form['actions']['cancel'] = [
      '#type' => 'submit',
      '#value' => $this->t('Cancel'),
      '#weight' => -10,
      '#ajax' => ['callback' => '::closeDialog'],
    ];
    return $form;
  }

  /**
   * Add the queuer.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   */
  public function addQueuer(array &$form, FormStateInterface $form_state) {
    $response = new AjaxResponse();
    $id = $form_state->getValue('id');
    $response->addCommand(new CloseModalDialogCommand());
    if (in_array($id, $this->purgeQueuers->getPluginsAvailable())) {
      $enabled = $this->purgeQueuers->getPluginsEnabled();
      $enabled[] = $id;
      $this->purgeQueuers->setPluginsEnabled($enabled);
      $response->addCommand(new ReloadConfigFormCommand('edit-queue'));
    }
    return $response;
  }

}
