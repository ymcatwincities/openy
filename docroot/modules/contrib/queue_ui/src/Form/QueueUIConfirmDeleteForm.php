<?php

namespace Drupal\queue_ui\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\user\PrivateTempStoreFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;

class QueueUIConfirmDeleteForm extends ConfirmFormBase {

  /**
   * @var PrivateTempStoreFactory
   */
  private $tempStoreFactory;

  public function __construct(PrivateTempStoreFactory $temp_store_factory) {
    $this->tempStoreFactory = $temp_store_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('user.private_tempstore')
    );
  }

  public function getFormId() {
    return 'queue_ui_confirm_delete_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    // Retrieve the queues to be deleted from the temp store.
    $queues = $this->tempStoreFactory
      ->get('queue_ui_delete_queues')
      ->get($this->currentUser()->id());
    if (!$queues) {
      return $this->redirect('queue_ui.overview_form');
    }

    return parent::buildForm($form, $form_state);
  }

  public function getQuestion() {
    $queues = $this->tempStoreFactory
      ->get('queue_ui_delete_queues')
      ->get($this->currentUser()->id());

    return $this->formatPlural(count($queues), 'Are you sure you want to delete the queue?', 'Are you sure you want to delete @count queues?');
  }

  public function getDescription() {
    return t('All items in each queue will be deleted, regardless of if leases exist. This operation cannot be undone.');
  }

  public function getCancelUrl() {
    return new Url('queue_ui.overview_form');
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    drupal_set_message($this->t('Queues deleted'));

    $queues = $this->tempStoreFactory
      ->get('queue_ui_delete_queues')
      ->get($this->currentUser()->id());

    foreach ($queues as $name) {
      $queue = \Drupal::queue($name);
      $queue->deleteQueue();
    }
    drupal_set_message($this->formatPlural(count($queues), 'Queue deleted', '@count queues deleted'));

    $form_state->setRedirect('queue_ui.overview_form');
  }
}