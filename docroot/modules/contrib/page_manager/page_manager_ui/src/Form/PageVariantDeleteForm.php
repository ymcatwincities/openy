<?php

/**
 * @file
 * Contains Drupal\page_manager_ui\Form\PageVariantDeleteForm.
 */

namespace Drupal\page_manager_ui\Form;

use Drupal\Core\Entity\EntityConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Builds the form to delete a PageVariant.
 */
class PageVariantDeleteForm extends EntityConfirmFormBase {

  /**
   * The entity being used by this form.
   *
   * @var \Drupal\page_manager\PageVariantInterface
   */
  protected $entity;

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete %name?', ['%name' => $this->entity->label()]);
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('entity.page.edit_form', [
      'page' => $this->entity->get('page'),
    ]);
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

    drupal_set_message($this->t('The variant %label has been removed.', [
      '%label' => $this->entity->label(),
    ]));

    $form_state->setRedirectUrl($this->getCancelUrl());
  }

}
