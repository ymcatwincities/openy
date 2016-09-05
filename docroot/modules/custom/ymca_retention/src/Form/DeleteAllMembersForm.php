<?php

namespace Drupal\ymca_retention\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Provides a form for deleting a ymca_retention_member entities.
 *
 * @ingroup ymca_retention_member
 */
class DeleteAllMembersForm extends ConfirmFormBase {
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'ymca_retention_member_delete_all';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete all members and their activities?');
  }

  /**
   * {@inheritdoc}
   *
   * If the delete command is canceled, return to the Member list.
   */
  public function getCancelUrl() {
    return new Url('entity.ymca_retention_member.collection');
  }

  /**
   * {@inheritdoc}
   *
   * Delete the entity and log the event. log() replaces the watchdog.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $operations = [
      [
        [
          '\Drupal\ymca_retention\Controller\MembersController',
          'deleteAllMembersProcessBatch',
        ],
        [],
      ],
    ];
    $batch = [
      'title' => t('Deleting members'),
      'operations' => $operations,
      'finished' => [
        '\Drupal\ymca_retention\Controller\MembersController',
        'deleteAllMembersFinishBatch',
      ],
    ];
    batch_set($batch);
  }

}
