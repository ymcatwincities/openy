<?php

namespace Drupal\contact_storage\Form;

use Drupal\Core\Entity\EntityConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Provides the contact form disable form.
 */
class ContactFormDisableForm extends EntityConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to disable the contact form %form?', ['%form' => $this->entity->label()]);
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('entity.contact_form.collection');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Disable');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->t('Disabled contact forms are not displayed. This action can be undone from the contact forms administration page.');
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    $form['contact_storage_disabled_form_message'] = [
      '#type' => 'textfield',
      '#title' => t('Default disabled contact form message'),
      '#description' => t('Default message to display if the contact form is disabled. It will be saved when clicking "Disable".'),
      '#default_value' => $this->getEntity()->getThirdPartySetting('contact_storage', 'disabled_form_message', $this->t('This contact form has been disabled.')),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Save the default disabled form message.
    $this->entity->setThirdPartySetting('contact_storage', 'disabled_form_message', $form_state->getValue('contact_storage_disabled_form_message'));
    $this->entity->disable()->save();
    drupal_set_message($this->t('Disabled contact form %form.', ['%form' => $this->entity->label()]));
    $form_state->setRedirectUrl($this->getCancelUrl());
  }

}
