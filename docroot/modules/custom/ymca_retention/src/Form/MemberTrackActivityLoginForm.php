<?php

namespace Drupal\ymca_retention\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Member Track activity login form.
 */
class MemberTrackActivityLoginForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'ymca_retention_track_activity_login_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $verify_membership_id = $form_state->getTemporaryValue('verify_membership_id');
    if (empty($verify_membership_id)) {
      $form['mail'] = [
        '#type' => 'email',
        '#required' => TRUE,
        '#attributes' => [
          'placeholder' => [
            $this->t('Your e-mail'),
          ],
        ],
      ];
    }
    else {
      $form['mail'] = [
        '#type' => 'hidden',
      ];
      $form['membership_id'] = [
        '#type' => 'textfield',
        '#required' => TRUE,
        '#attributes' => [
          'placeholder' => [
            $this->t('Your facility access ID'),
          ],
        ],
      ];

    }

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => t('Ok'),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $verify_membership_id = $form_state->getTemporaryValue('verify_membership_id');
    if (empty($verify_membership_id) && !array_key_exists('membership_id', $form)) {
      $mail = $form_state->getValue('mail');
      $query = \Drupal::entityQuery('ymca_retention_member')
        ->condition('mail', $mail);
      $result = $query->execute();
      if (empty($result)) {
        $form_state->setErrorByName('mail', $this->t('Member with email %value is not registered. Please register.', [
          '%value' => $mail,
        ]));
        return;
      }
      if (count($result) > 1) {
        $form_state->setTemporaryValue('verify_membership_id', TRUE);
        $form_state->setRebuild(TRUE);
      }
      else {
        $form_state->setTemporaryValue('member', reset($result));
      }
    }
    else {
      $membership_id = $form_state->getValue('membership_id');
      $mail = $form_state->getValue('mail');
      $query = \Drupal::entityQuery('ymca_retention_member')
        ->condition('mail', $mail)
        ->condition('membership_id', $membership_id);
      $result = $query->execute();
      if (empty($result)) {
        $form_state->setErrorByName('mail', $this->t('Member with email %mail and facility id %fai is not registered. Please register.', [
          '%mail' => $mail,
          '%fai' => $membership_id,
        ]));
        return;
      }
      else {
        $form_state->setTemporaryValue('member', reset($result));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $member = $form_state->getTemporaryValue('member');
    /** @var \Drupal\user\SharedTempStore $temp_store */
    $temp_store = \Drupal::service('user.shared_tempstore')
      ->get('ymca_retention');
    $temp_store->setIfOwner('member', $member);

    // Redirect to confirmation page.
    $form_state->setRedirect('page_manager.page_view_ymca_retention_pages', ['string' => 'enroll-success']);
  }

}
