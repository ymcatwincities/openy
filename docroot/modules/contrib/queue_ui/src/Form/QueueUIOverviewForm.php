<?php

namespace Drupal\queue_ui\Form;

use Drupal\Core\Queue\QueueFactory;
use Drupal\Core\Queue\QueueInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\State\State;
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
   * @var \Drupal\Core\State\State
   */
  protected $state;

  /**
   * @param \Drupal\Core\Queue\QueueFactory $queue_factory
   * @param \Drupal\user\PrivateTempStoreFactory $temp_store_factory
   * @param \Drupal\Core\Session\AccountInterface $current_user
   * @param \Drupal\Core\State\State $state
   */
  public function __construct(QueueFactory $queue_factory, PrivateTempStoreFactory $temp_store_factory, AccountInterface $current_user, State $state) {
    $this->queueFactory = $queue_factory;
    $this->tempStoreFactory = $temp_store_factory;
    $this->currentUser = $current_user;
    $this->state = $state;
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

    $header = array(
      'title' => t('Title'),
      'items' => t('Number of items'),
      'class' => t('Class'),
      'cron' => t('Time limit per cron run'),
//      'inspect' => t('Inspect'),
    );
    // Get queues defined by plugins.
    $defined_queues = queue_ui_defined_queues();
    // Get queues names.


    $options = array();
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

      $options[$name] = array(
        'title' => $title,
        'items' => $queue->numberOfItems(),
        'class' => $class_name,
        'cron' => $cron_time_limit,
      );

//        // If queue inspection is enabled for this class, add to the options array.
//        if ($inspect) {
//          $options[$name]['inspect'] = array('data' => l(t('Inspect'), QUEUE_UI_BASE . "/inspect/$name"));
//        }
//        else {
//          $options[$name]['inspect'] = '';
//        }
    }

    $form['queues'] = array(
      '#type' => 'tableselect',
      '#header' => $header,
      '#options' => $options,
      '#empty' => t('No queues defined'),
    );

    // @todo deactivate options
    // Option to run batch.
    $form['batch'] = array(
      '#type' => 'submit',
      '#value' => t('Batch process'),
      '#submit' => ['::submitBatch'],
    );
    // Option to remove lease timestamps.
    $form['release'] = array(
      '#type' => 'submit',
      '#value' => t('Remove leases'),
      '#submit' => ['::submitRelease'],
    );
    // Option to run via cron.
    // @todo: Define a better way to set the time allocated for each queue.
//    $form['cron'] = array(
//      '#type' => 'submit',
//      '#value' => t('Cron process'),
//      '#submit' => ['::submitCron'],
//    );
    // Option to delete queue.
    $form['delete'] = array(
      '#type' => 'submit',
      '#value' => t('Clear'),
      '#submit' => ['::submitClear'],
    );
    // Specify our step submit callback.
    $form['step_submit'] = array('#type' => 'value', '#value' => 'queue_ui_overview_submit');
    return $form;
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    // We need this method, but each button has its own submit handler.
  }

  public function submitBatch(array &$form, FormStateInterface $form_state) {
    // Process queue(s) with batch.
    $selected_queues = array_filter($form_state->getValue('queues'));
    foreach ($selected_queues as $queue_name) {

      $queue = $this->queueFactory->get($queue_name);

      $batch = [
        'operations' => []
      ];

      foreach (range(1, $queue->numberOfItems()) as $index) {
        $batch['operations'][] = ['\Drupal\queue_ui\QueueUIBatch::step', [$queue_name]];
      }
      batch_set($batch);
    }
  }

  public function submitCron(array &$form, FormStateInterface $form_state) {

    $selected_queues = array_filter($form_state->getValue('queues'));

    foreach ($selected_queues as $name) {
      $this->state->set('queue_ui_cron_' . $name, 10);
    }

    // Clear the cached plugin definition so that changes come into effect.
    \Drupal::service('plugin.manager.queue_worker')->clearCachedDefinitions();
  }

  public function submitClear(array &$form, FormStateInterface $form_state) {
    $queues = array_filter($form_state->getValue('queues'));

    $this->tempStoreFactory->get('queue_ui_delete_queues')->set($this->currentUser->id(), $queues);

    $form_state->setRedirect('queue_ui.confirm_delete_form');
  }

  public function submitRelease(array &$form, FormStateInterface $form_state) {
    $selected_queues = array_filter($form_state->getValue('queues'));
    foreach ($selected_queues as $name) {
      $num_updated = db_update('queue')
        ->fields(array(
          'expire' => 0,
        ))
        ->condition('name', $name, '=')
        ->execute();
      drupal_set_message(t('@count lease reset in queue @name', array('@count' => $num_updated, '@name' => $name)));
    }
  }
}
