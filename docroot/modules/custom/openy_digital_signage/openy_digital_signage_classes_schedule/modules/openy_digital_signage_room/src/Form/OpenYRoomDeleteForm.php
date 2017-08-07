<?php

namespace Drupal\openy_digital_signage_room\Form;

use Drupal\Core\Entity\ContentEntityDeleteForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a form for deleting Digital Signage Room entities.
 *
 * @ingroup openy_digital_signage_room
 */
class OpenYRoomDeleteForm extends ContentEntityDeleteForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return parent::getQuestion();
  }

}
