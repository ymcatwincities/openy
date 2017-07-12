<?php

namespace Drupal\queue_ui\Form;

use Drupal\Core\Database\Database;
use Drupal\Core\Queue\QueueFactory;
use Drupal\Core\Queue\QueueInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\State\StateInterface;
use Drupal\user\PrivateTempStoreFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

class QueueUIOverviewForm extends FormBase {

  /**
   * The queue factory.
   *
   * @var \Drupal\Core\Queue\QueueFactory
   */
  protected $queueFactory;

  /**
   * The tempstore factory.
   *
   * @var \Drupal\user\PrivateTempStoreFactory
   */
  protected $tempStoreFactory;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The Drupal state storage.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Database
   */
  protected $dbConnection;

  /**
   * @param \Drupal\Core\Queue\QueueFactory $queue_factory
   * @param \Drupal\user\PrivateTempStoreFactory $temp_store_factory
   * @param \Drupal\Core\Session\AccountInterface $current_user
   * @param \Drupal\Core\State\StateInterface $state
   */
  public function __construct(QueueFactory $queue_factory, PrivateTempStoreFactory $temp_store_factory, AccountInterface $current_user, StateInterface $state) {
    $this->queueFactory = $queue_factory;
    $this->tempStoreFactory = $temp_store_factory;
    $this->currentUser = $current_user;
    $this->state = $state;
    $this->dbConnection = Database::getConnection('default');
  }


  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('queue'),
      $container->get('user.private_tempstore'),
      $container->get('current_user'),
      $container->get('state')
    );
  }

  public function getFormId() {
    return 'queue_ui_overview_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    // @todo add inspection back
    // @todo activation status

    $header = [
      'title' => t('Title'),
      'items' => t('Number of items'),
      'class' => t('Class'),
      'cron' => t('Time limit per cron run'),
//      'inspect' => t('Inspect'),
    ];
    // Get queues defined by plugins.
    $defined_queues = queue_ui_defined_queues();
    // Get queues names.


    $options = [];
    foreach ($defined_queues as $name => $queue_definition) {
      /** @var QueueInterface $queue */
      $queue = $this->queueFactory->get($name);

      $namespace = explode('\\', get_class($queue));
      $class_name = array_pop($namespace);

//      $class_info = QueueUI::get('Drupal\queue_ui\QueueUI' . $class_name);

      $inspect = FALSE;

      $title = (string)$queue_definition['title'];

      if (isset($queue_definition['cron']['time']) && $queue_definition['cron']['time'] != 0) {
        $cron_time_limit = $this->formatPlural($queue_definition['cron']['time'], '@count second', '@count seconds');
      }
      else {
        $cron_time_limit = $this->t('Off');
      }

//        if (is_object($class_info) && $class_info->inspect) {
//          $inspect = TRUE;
//        }

      $options[$name] = [
        'title' => $title,
        'items' => $queue->numberOfItems(),
        'class' => $class_name,
        'cron' => $cron_time_limit,
      ];

//        // If queue inspection is enabled for this class, add to the options array.
//        if ($inspect) {
//          $options[$name]['inspect'] = array('data' => l(t('Inspect'), QUEUE_UI_BASE . "/inspect/$name"));
//        }
//        else {
//          $options[$name]['inspect'] = '';
//        }
    }

    $form['top'] = [
      'operation' => [
        '#type' => 'select',
        '#title' => t('Action'),
        '#options' => [
          'submitBatch' => t('Batch process'),
          // @todo: Define a better way to set the time allocated for each queue.
          // 'submitCron' => t('Cron process'),
          'submitRelease' => t('Remove leases'),
          'submitClear' => t('Clear'),
        ],
      ],
      'actions' => [
        '#type' => 'container',
        '#attributes' => [
          'class' => ['form-actions'],
        ],
        'apply' => [
          '#type' => 'submit',
          '#submit' => ['::submitBulkForm'],
          '#value' => t('Apply to selected items'),
        ],
      ],
    ];

    $form['queues'] = [
      '#type' => 'tableselect',
      '#header' => $header,
      '#options' => $options,
      '#empty' => t('No queues defined'),
    ];

    $form['botton'] = [
      'actions' => [
        '#type' => 'container',
        '#attributes' => [
          'class' => ['form-actions'],
        ],
        'apply' => [
          '#type' => 'submit',
          '#submit' => ['::submitBulkForm'],
          '#value' => t('Apply to selected items'),
        ],
      ],
    ];

    // Specify our step submit callback.
    $form['step_submit'] = ['#type' => 'value', '#value' => 'queue_ui_overview_submit'];
    return $form;
  }

  /**
   * We need this method, but each button has its own submit handler.
   *
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {}

  /**
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   */
  public function submitBulkForm(array &$form, FormStateInterface $form_state) {
    if (in_array($form_state->getValue('operation'), ['submitBatch', 'submitCron', 'submitRelease', 'submitClear'])) {
      $selected_queues = array_filter($form_state->getValue('queues'));

      if (!empty($selected_queues)) {
        $this->{$form_state->getValue('operation')}($form_state, $selected_queues);
      }
    }
  }

  /**
   * Process queue(s) with batch.
   *
   * @param $form_state
   * @param $queues
   */
  public function submitBatch($form_state, $queues) {
    $batch = [
      'operations' => []
    ];

    foreach ($queues as $queue_name) {
      $queue = $this->queueFactory->get($queue_name);

      if ($queue->numberOfItems()) {
        foreach (range(1, $queue->numberOfItems()) as $index) {
          $batch['operations'][] = ['\Drupal\queue_ui\QueueUIBatch::step', [$queue_name]];
        }
      }
    }

    batch_set($batch);
  }

  /**
   * Option to run via cron.
   *
   * @param $form_state
   * @param $queues
   */
  public function submitCron($form_state, $queues) {
    foreach ($queues as $name) {
      $this->state->set('queue_ui_cron_' . $name, 10);
    }

    // Clear the cached plugin definition so that changes come into effect.
    \Drupal::service('plugin.manager.queue_worker')->clearCachedDefinitions();
  }

  /**
   * Option to remove lease timestamps.
   *
   * @param $form_state
   * @param $queues
   */
  public function submitRelease($form_state, $queues) {
    foreach ($queues as $name) {
      $num_updated = $this->dbConnection->update('queue')
        ->fields([
          'expire' => 0,
        ])
        ->condition('name', $name, '=')
        ->execute();
      drupal_set_message(t('@count lease reset in queue @name', ['@count' => $num_updated, '@name' => $name]));
    }
  }

  /**
   * Option to delete queue.
   *
   * @param $form_state
   * @param $queues
   */
  public function submitClear($form_state, $queues) {
    $this->tempStoreFactory->get('queue_ui_delete_queues')->set($this->currentUser->id(), $queues);

    $form_state->setRedirect('queue_ui.confirm_delete_form');
  }
}
