<?php

namespace Drupal\ymca_retention\Form;

use Drupal\Core\Entity\ContentEntityConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Provides a form for deleting a ymca_retention_member entity.
 *
 * @ingroup ymca_retention_member
 */
class MemberDeleteForm extends ContentEntityConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete entity %name?', [
      '%name' => $this->entity->label(),
    ]);
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
   */
  public function getConfirmText() {
    return $this->t('Delete');
  }

  /**
   * {@inheritdoc}
   *
   * Delete the entity and log the event. log() replaces the watchdog.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $entity = $this->getEntity();
    $entity->delete();

    \Drupal::logger('ymca_retention_member')->notice('@type: deleted %title.', [
      '@type' => $this->entity->bundle(),
      '%title' => $this->entity->label(),
    ]);
    $form_state->setRedirect('entity.ymca_retention_member.collection');
  }

}
