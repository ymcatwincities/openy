<?php

namespace Drupal\file_entity\Form;

use Drupal\Core\Entity\EntityConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\file_entity\Entity\FileType;

/**
 * Builds the form to enable a file type.
 */
class FileTypeEnableForm extends EntityConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return t(
      'Are you sure you want to enable the file type %name?',
      array('%name' => $this->entity->label())
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return t('Enable');
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    /** @var FileType $type */
    $type = $this->entity;
    $type->enable()->save();
    drupal_set_message(t(
      'The file type %label has been enabled.',
      array('%label' => $type->label())
    ));
    $form_state->setRedirect('entity.file_type.collection');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('entity.file_type.collection');
  }
}
