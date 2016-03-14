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

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $message = $this->entity;
    $user = $this->currentUser();
    $this->mailHandler->sendMailMessages($message, $user);

    $this->flood->register('contact', $this->config('contact.settings')->get('flood.interval'));

    // Allow modules to alter messages.
    $user_messages = ['Your message has been sent'];
    \Drupal::moduleHandler()->alter('webforms_sent_message', $user_messages, $message);

    foreach ($user_messages as $user_message) {
      drupal_set_message($this->t($user_message));
    }

    // To avoid false error messages caused by flood control, redirect away from
    // the contact form; either to the contacted user account or the front page.
    if ($message->isPersonal() && $user->hasPermission('access user profiles')) {
      $form_state->setRedirectUrl($message->getPersonalRecipient()->urlInfo());
    }
    else {
      $form_state->setRedirect('<front>');
    }
    // Save the message. In core this is a no-op but should contrib wish to
    // implement message storage, this will make the task of swapping in a real
    // storage controller straight-forward.
    $message->save();
  }

}
