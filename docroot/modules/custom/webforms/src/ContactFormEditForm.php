<?php

/**
 * @file
 * Contains \Drupal\contact\ContactFormEditForm.
 */

namespace Drupal\webforms;

use Drupal\Core\Form\FormStateInterface;
use Drupal\contact\ContactFormEditForm as CoreContactFormEditForm;

/**
 * Extended base form for contact form edit forms.
 */
class ContactFormEditForm extends CoreContactFormEditForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $contact_form = $this->entity;
    $form['prefix'] = array(
      '#type' => 'textarea',
      '#title' => $this->t('Prefix'),
      '#default_value' => $contact_form->getPrefix(),
      '#description' => $this->t('Optional prefix.'),
    );
    $form['suffix'] = array(
      '#type' => 'textarea',
      '#title' => $this->t('Suffix'),
      '#default_value' => $contact_form->getSuffix(),
      '#description' => $this->t('Optional suffix.'),
    );

    return $form;
  }

}
