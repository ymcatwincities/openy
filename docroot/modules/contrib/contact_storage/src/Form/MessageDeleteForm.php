<?php

/**
 * @file
 * Contains \Drupal\contact_storage\Form\MessageDeleteForm.
 */

namespace Drupal\contact_storage\Form;

use Drupal\Core\Entity\ContentEntityConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a deletion confirmation form for contact message entity.
 */
class MessageDeleteForm extends ContentEntityConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'contact_message_delete';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete the contact message %subject?', array('%subject' => $this->entity->getSubject()));
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return $this->getEntity()->urlInfo('collection');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Delete');
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->entity->delete();
    drupal_set_message($this->t('Deleted contact message %subject.', array('%subject' => $this->entity->getSubject())));
    $this->logger('contact_storage')->notice('Deleted contact message %subject.', array('%subject' => $this->entity->getSubject()));
    $form_state->setRedirectUrl($this->getCancelUrl());
  }

}
