<?php

namespace Drupal\openy_schedules\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Language\Language;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for the repeat entity edit forms.
 *
 * @ingroup openy_schedules
 */
class RepeatForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /* @var $entity \Drupal\openy_schedules\Entity\Repeat */
    $form = parent::buildForm($form, $form_state);
    $entity = $this->entity;

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $status = parent::save($form, $form_state);

    $entity = $this->entity;
    if ($status == SAVED_UPDATED) {
      drupal_set_message($this->t('The repeat %repeat has been updated.', [
        '%repeat' => $entity->label()
      ]));
    } else {
      drupal_set_message($this->t('The repeat %repeat has been added.', [
        '%repeat' => $entity->label()
      ]));
    }

    $form_state->setRedirect('entity.repeat.canonical', ['repeat' => $entity->id()]);
    return $status;
  }
}
