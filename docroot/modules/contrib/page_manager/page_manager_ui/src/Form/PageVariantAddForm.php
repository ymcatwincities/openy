<?php

/**
 * @file
 * Contains Drupal\page_manager_ui\Form\PageVariantAddForm.
 */

namespace Drupal\page_manager_ui\Form;

use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a form for adding a variant.
 */
class PageVariantAddForm extends PageVariantFormBase {

  /**
   * {@inheritdoc}
   */
  protected function submitText() {
    return $this->t('Add variant');
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    parent::save($form, $form_state);
    $form_state->setRedirectUrl($this->getEntity()->toUrl('edit-form'));
  }

}
