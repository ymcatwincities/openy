<?php

namespace Drupal\ymca_retention\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\ymca_retention\Ajax\YmcaRetentionModalHideCommand;
use Drupal\ymca_retention\AnonymousCookieStorage;
use Drupal\ymca_retention\Entity\Member;

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

    if (!$tab_id = $form_state->get('tab_id')) {
      $tab_id = 'about';
    }

    $form['tab_id'] = ['#type' => 'hidden', '#default_value' => $tab_id];
    $verify_membership_id = $form_state->getTemporaryValue('verify_membership_id');

    if ($verify_membership_id === NULL) {
      $verify_membership_id = $config['verify_membership_id'];
      $form_state->setTemporaryValue('verify_membership_id', $verify_membership_id);
    }

    $validate = [get_class($this), 'elementValidateRequired'];
    if (empty($verify_membership_id)) {
      $form['mail'] = [
        '#type' => 'email',
        '#required' => TRUE,
        '#attributes' => [
          'placeholder' => [
            $this->t('Your email'),
          ],
        ],
        '#element_required_error' => $this->t('Email is required.'),
        '#element_validate' => [
          ['\Drupal\Core\Render\Element\Email', 'validateEmail'],
          $validate,
        ],
        '#skip_ymca_preprocess' => TRUE,
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
            $this->t('Your member ID'),
          ],
        ],
        '#element_required_error' => $this->t('Member ID is required.'),
        '#element_validate' => [$validate],
        '#skip_ymca_preprocess' => TRUE,
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
          'campaign-blue',
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

    $form['refresh'] = [
      '#type' => 'button',
      '#attributes' => [
        'style' => [
          'display:none',
        ],
        'class' => [
          'refresh'
        ],
      ],
      '#value' => t('Refresh'),
      'method' => 'replaceWith',
      '#ajax' => [
        'callback' => [$this, 'ajaxFormRefreshCallback'],
        'event' => 'click',
      ],
    ];

    // @todo Fix for members, which already registered and does not have email.
    $lost_mail = $form_state->getTemporaryValue('lost_mail');
    if (!empty($lost_mail)) {
      $membership_id = $form_state->getTemporaryValue('membership_id');
      $query = \Drupal::entityQuery('ymca_retention_member')
        ->condition('membership_id', $membership_id)
        ->notExists('mail')
        ->notExists('personify_email');
      $result = $query->execute();
      if ($result) {
        $form['lost_mail'] = [
          '#type' => 'email',
          '#required' => TRUE,
          '#attributes' => [
            'placeholder' => [
              $this->t('Your email'),
            ],
          ],
          '#element_required_error' => $this->t('Email is required.'),
          '#element_validate' => [
            ['\Drupal\Core\Render\Element\Email', 'validateEmail'],
            $validate,
          ],
        ];
        $form['membership_id'] = [
          '#type' => 'hidden',
          '#value' => $membership_id,
        ];
      }
    }
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
   * Ajax form callback for clearing and refreshing form.
   *
   * @param array $form
   *   Form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse|array
   *   Ajax response.
   */
  public function ajaxFormRefreshCallback(array &$form, FormStateInterface $form_state) {
    // Clear error messages.
    drupal_get_messages('error');

    $ajax_response = new AjaxResponse();

    $this->refreshValues($form_state);
    $new_form = \Drupal::formBuilder()
      ->rebuildForm($this->getFormId(), $form_state, $form);

    // Refreshing form.
    $ajax_response->addCommand(new HtmlCommand('#ymca-retention-user-menu-login-form', $new_form));

    return $ajax_response;
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
      $ajax_response->addCommand(new YmcaRetentionModalHideCommand());
      return $ajax_response;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Get retention settings.
    $settings = \Drupal::config('ymca_retention.general_settings');
    $from_date = new \DateTime($settings->get('date_registration_open'));
    $to_date = new \DateTime($settings->get('date_registration_close'));
    $current_date = new \DateTime();
    // Validate that current date is less, than date when tracking activity page will be opened.
    if ($current_date < $from_date) {
      $form_state->setErrorByName('form', $this->t('Activity tracking begins %date when the Y Strive challenge open.', [
        '%date' => $from_date->format('F j'),
      ]));
      return;
    }
    // Validate that current date is higher, than date when tracking activity page was closed.
    if ($current_date > $to_date) {
      $form_state->setErrorByName('form', $this->t('The Y Strive challenge is now closed and activity is no longer able to be tracked.'));
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
      $query = \Drupal::entityQuery('ymca_retention_member')
        ->condition('membership_id', $membership_id);

      if ($mail = $form_state->getValue('mail')) {
        $query->condition('mail', $mail);
      }

      $result = $query->execute();
      if (empty($result)) {
        $args = ['%mid' => $membership_id];
        $msg = 'Member with member ID %mid is not registered. <a href="#" data-toggle="modal" data-target="#ymca-retention-modal" data-type="register">Please register.</a>';

        if ($mail) {
          $msg = 'Member with email %mail and member ID %mid is not registered. Please register.';
          $args['%mail'] = $mail;
        }

        $form_state->setErrorByName('membership_id', $this->t($msg, $args));
        return;
      }

      $form_state->setTemporaryValue('member', reset($result));

      // @todo Fix for members, which already registered and does not have email.
      $member = Member::load(reset($result));
      $email = $member->getEmail();
      $lost_mail = $form_state->getValue('lost_mail');
      if (empty($email) && empty($lost_mail)) {
        $form_state->setTemporaryValue('lost_mail', TRUE);
        $form_state->setTemporaryValue('membership_id', $membership_id);
        $form_state->setRebuild();
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $member_id = $form_state->getTemporaryValue('member');

    AnonymousCookieStorage::set('ymca_retention_member', $member_id);

    // @todo Fix for members, which already registered and does not have email.
    $lost_mail = $form_state->getValue('lost_mail');
    if (!empty($lost_mail)) {
      /* @var Member $member */
      $member = Member::load($member_id);
      $member->setEmail($lost_mail);
      $member->setPersonifyEmail($lost_mail);
      $member->save();
    }
  }

  /**
   * Refresh values.
   */
  protected function refreshValues(FormStateInterface $form_state) {
    $user_input = $form_state->getUserInput();
    unset($user_input['membership_id']);
    $form_state->setUserInput($user_input);
    $form_state->set('membership_id', NULL);
    $form_state->set('mail', NULL);
  }

}
