<?php

namespace Drupal\webforms;

use Drupal\Core\Form\FormStateInterface;
use Drupal\contact\MessageForm as CoreMessageForm;

/**
 * Modified form controller for contact message forms.
 */
class MessageForm extends CoreMessageForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $message = $this->entity;
    $form = parent::form($form, $form_state, $message);

    // Extract contact form and use it configuration.
    $contact_form = $message->getContactForm();
    $form['#prefix'] = $contact_form->getPrefix();
    $form['#suffix'] = $contact_form->getSuffix();

    return $form;
  }

}
