<?php

namespace Drupal\openy_campaign\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\Node;

/**
 * Form controller for the Simplified Team Member Registration Portal form.
 *
 * @ingroup openy_campaign_member
 */
class MemberRegistrationForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'openy_campaign_registration_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $destination = '') {
    $campaignIds = \Drupal::entityQuery('node')->condition('type','campaign')->execute();
    $campaigns = Node::loadMultiple($campaignIds);
    $options = [];
    foreach ($campaigns as $item) {
      $options[$item->id()] = $item->getTitle();
    }

    // Select Campaign to assign Member
    $form['campaign'] = [
      '#type' => 'select',
      '#title' => $this->t('Select Campaign'),
      '#options' => $options,
    ];

    // The id on the membership card.
    $form['memdership_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Scan the Membership ID'),
      '#default_value' => '',
      '#size' => 60,
      '#maxlength' => 128,
      '#required' => TRUE,
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => t('Register Team Member'),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
  }

}
