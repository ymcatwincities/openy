<?php

namespace Drupal\openy_campaign\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Provides a form for deleting a openy_campaign_member entities.
 */
class DeleteAllMembersForm extends ConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'openy_campaign_member_delete_all';
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
    return new Url('entity.openy_campaign_member.collection');
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
          '\Drupal\openy_campaign\Controller\MembersController',
          'deleteAllMembersProcessBatch',
        ],
        [],
      ],
    ];
    $batch = [
      'title' => t('Deleting members'),
      'operations' => $operations,
      'finished' => [
        '\Drupal\openy_campaign\Controller\MembersController',
        'deleteAllMembersFinishBatch',
      ],
    ];
    batch_set($batch);
  }

}
