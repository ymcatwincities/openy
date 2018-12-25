<?php

namespace Drupal\purge_ui\Form;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\CloseModalDialogCommand;
use Drupal\purge\Plugin\Purge\Queue\QueueServiceInterface;
use Drupal\purge_ui\Form\CloseDialogTrait;
use Drupal\purge_ui\Form\ReloadConfigFormCommand;

/**
 * The queue data browser.
 */
class QueueChangeForm extends FormBase {
  use CloseDialogTrait;

  /**
   * @var \Drupal\purge\Plugin\Purge\Queue\QueueServiceInterface
   */
  protected $purgeQueue;

  /**
   * Constructs a QueueChangeForm object.
   *
   * @param \Drupal\purge\Plugin\Purge\Queue\QueueServiceInterface $purge_queue
   *   The purge queue service.
   *
   * @return void
   */
  public function __construct(QueueServiceInterface $purge_queue) {
    $this->purgeQueue = $purge_queue;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('purge.queue'));
  }

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'purge_ui.queue_change_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['#attached']['library'][] = 'core/drupal.dialog.ajax';
    $form['warning'] = [
      '#prefix' => '<p>',
      '#suffix' => '</p>',
      '#markup' => $this->t("<p>The queue engine is the underlying plugin which stores, retrieves and deletes invalidation instructions in the system. When queuers add items to the queue or when processors claim items from it, it is this engine that stores it physically. For most cases the <b>database</b> engine is sufficient, however, in extremely busy scenarios more efficient engines can bring the necessary relief.</p><p><b>Warning: </b> when you change the queue, it will be emptied as well!</p>"),
    ];
    $form['plugin_id'] = [
      '#type' => 'tableselect',
      '#default_value' => current($this->purgeQueue->getPluginsEnabled()),
      '#responsive' => TRUE,
      '#multiple' => FALSE,
      '#options' => [],
      '#header' => [
        'label' => $this->t('Engine'),
        'description' => $this->t('Description'),
      ],
    ];
    foreach ($this->purgeQueue->getPlugins() as $plugin_id => $definition) {
      $form['plugin_id']['#options'][$plugin_id] = [
        'label' => $definition['label'],
        'description' => $definition['description'],
      ];
    }
    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['cancel'] = [
      '#type' => 'submit',
      '#value' => $this->t('Cancel'),
      '#weight' => -10,
      '#button_type' => 'primary',
      '#ajax' => ['callback' => '::closeDialog'],
    ];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t("Change"),
      '#button_type' => 'danger',
      '#ajax' => ['callback' => '::changeQueue'],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function changeQueue(array &$form, FormStateInterface $form_state) {
    $plugin_id = $form_state->getValue('plugin_id');
    $response = new AjaxResponse();
    $response->addCommand(new CloseModalDialogCommand());
    if (in_array($plugin_id, array_keys($this->purgeQueue->getPlugins()))) {
      $response->addCommand(new ReloadConfigFormCommand('edit-queue'));
      $this->purgeQueue->setPluginsEnabled([$plugin_id]);
    }
    return $response;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $plugin_id = $form_state->getValue('plugin_id');
    if (in_array($plugin_id, array_keys($this->purgeQueue->getPlugins()))) {
      $this->purgeQueue->setPluginsEnabled([$plugin_id]);
    }
  }

}
