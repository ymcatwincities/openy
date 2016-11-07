<?php

namespace Drupal\tango_card\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\UrlHelper;

/**
 * Form handler for the Tango Card campaign edit forms.
 */
class CampaignForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    $states = [
      ':input[name="send_email[value]"]' => ['checked' => TRUE],
    ];

    $form['email'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Notification email'),
      '#weight' => 1,
      '#states' => ['visible' => $states],
    ];

    foreach (['template', 'from', 'subject', 'message'] as $suffix) {
      $field = 'email_' . $suffix;

      $form['email'][$field] = $form[$field];
      $form['email'][$field]['widget'][0]['value']['#states'] = ['required' => $states];
      unset($form[$field]);
    }

    $form['email']['email_template']['widget'][0]['value']['#states'] = [];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity = $this->getEntity();
    $entity->save();

    $form_state->setRedirect('entity.tango_card_campaign.collection');
  }

}
