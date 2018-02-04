<?php

namespace Drupal\openy_campaign\Form;

use Drupal\Core\Form\FormStateInterface;

/**
 * MemberRegistrationSimpleForm.
 */
class MemberRegistrationSimpleForm extends MemberRegisterForm {

  protected static $containerId = 'openy_campaign_register_form';

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'openy_campaign_registration_simple_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $campaign_id = NULL) {
    return parent::buildForm($form, $form_state, $campaign_id);
  }

}
