<?php

/**
 * @file
 * Contains \Drupal\page_manager_ui\Form\StaticContextEditForm.
 */

namespace Drupal\page_manager_ui\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\page_manager\PageVariantInterface;

/**
 * Provides a form for adding a new static context.
 */
class StaticContextEditForm extends StaticContextFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'page_manager_static_context_edit_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function submitButtonText() {
    return $this->t('Update Static Context');
  }

  /**
   * {@inheritdoc}
   */
  protected function submitMessageText() {
    return $this->t('The %label static context has been updated.', ['%label' => $this->staticContext['label']]);
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, PageVariantInterface $page_variant = NULL, $name = '') {
    $form = parent::buildForm($form, $form_state, $page_variant, $name);
    // The machine name of an existing context is read-only.
    $form['machine_name'] = array(
      '#type' => 'value',
      '#value' => $name,
    );
    return $form;
  }

}
