<?php

namespace Drupal\ymca_retention\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\RedirectCommand;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\ymca_retention\AnonymousCookieStorage;

/**
 * Member Track activity login form.
 */
class MemberLoginForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'ymca_retention_login_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $form_state->getBuildInfo()['args'][0];
    if (isset($config['theme'])) {
      $form['#theme'] = $config['theme'];
    }

    $verify_membership_id = $form_state->getTemporaryValue('verify_membership_id');
    $validate = [get_class($this), 'elementValidateRequired'];
    if (empty($verify_membership_id)) {
      $form['mail'] = [
        '#type' => 'email',
        '#required' => TRUE,
        '#attributes' => [
          'placeholder' => [
            $this->t('Your e-mail'),
          ],
        ],
        '#element_required_error' => $this->t('Email is required.'),
        '#element_validate' => [
          ['\Drupal\Core\Render\Element\Email', 'validateEmail'],
          $validate,
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
        '#element_required_error' => $this->t('Facility access ID is required.'),
        '#element_validate' => $validate,
      ];
    }

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => t('Ok'),
      '#attributes' => [
        'class' => [
          'btn',
          'btn-lg',
          'btn-primary',
          'orange-light-lighter',
        ],
      ],
      '#ajax' => [
        'callback' => [$this, 'ajaxFormCallback'],
        'method' => 'replaceWith',
        'wrapper' => isset($config['wrapper']) ? $config['wrapper'] : 'report .report-form form',
        'progress' => [
          'type' => 'throbber',
          'message' => NULL,
        ],
      ],
    ];
    return $form;
  }

  /**
   * Set a custom validation error on the #required element.
   *
   * @param array $element
   *   Form element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state.
   */
  public function elementValidateRequired(array $element, FormStateInterface $form_state) {
    if (!empty($element['#required_but_empty']) && isset($element['#element_required_error'])) {
      $form_state->setError($element, $element['#element_required_error']);
    }
  }

  /**
   * Ajax form callback for displaying errors or redirecting.
   *
   * @param array $form
   *   Form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse|array
   *   Ajax response.
   */
  public function ajaxFormCallback(array &$form, FormStateInterface $form_state) {
    if ($form_state->isRebuilding()) {
      return $form;
    }
    if ($form_state->hasAnyErrors()) {
      $form['messages'] = ['#type' => 'status_messages'];
      return $form;
    }
    else {
      // Instantiate an AjaxResponse Object to return.
      $ajax_response = new AjaxResponse();
      $ajax_response->addCommand(new RedirectCommand(Url::fromRoute('page_manager.page_view_ymca_retention_pages', [
        'string' => 'activity',
      ])->toString()));
      return $ajax_response;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Get retention settings.
    $settings = \Drupal::config('ymca_retention.general_settings');
    $from_date = new \DateTime($settings->get('date_reporting_open'));
    $to_date = new \DateTime($settings->get('date_reporting_close'));
    $current_date = new \DateTime();
    // Validate that current date is less, than date when tracking activity page will be opened.
    if ($current_date < $from_date) {
      $form_state->setErrorByName('form', $this->t('Activity tracking begins %date when the Y Games open.', [
        '%date' => $from_date->format('F j'),
      ]));
      return;
    }
    // Validate that current date is higher, than date when tracking activity page was closed.
    if ($current_date > $to_date) {
      $form_state->setErrorByName('form', $this->t('The Y Games are now closed and activity is no longer able to be tracked.'));
      return;
    }

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
      // If form element has errors, just return nothing.
      if ($form_state->getError($form['membership_id'])) {
        return;
      }
      // Get membership id and try find it in database.
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
      $form_state->setTemporaryValue('member', reset($result));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $member_id = $form_state->getTemporaryValue('member');

    AnonymousCookieStorage::set('ymca_retention_member', $member_id);

    // Redirect to confirmation page.
    $form_state->setRedirect('page_manager.page_view_ymca_retention_pages', [
      'string' => 'activity',
    ]);
  }

}
