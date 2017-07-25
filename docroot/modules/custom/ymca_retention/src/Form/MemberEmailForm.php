<?php

namespace Drupal\ymca_retention\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\ymca_retention\AnonymousCookieStorage;
use Drupal\ymca_retention\Entity\Member;

/**
 * Member email form.
 */
class MemberEmailForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'ymca_retention_email_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $form_state->getBuildInfo()['args'][0];
    if (isset($config['theme'])) {
      $form['#theme'] = $config['theme'];
    }

    // TODO: Do we really need this tab_id here?
    if (!$tab_id = $form_state->get('tab_id')) {
      $tab_id = 'about';
    }
    $form['tab_id'] = ['#type' => 'hidden', '#default_value' => $tab_id];

    $validate_required = [get_class($this), 'elementValidateRequired'];

    $form['email'] = [
      '#type' => 'email',
      '#title' => $this->t('Email'),
      '#title_display' => 'hidden',
      '#default_value' => '',
      '#required' => TRUE,
      '#attributes' => [
        'placeholder' => [
          $this->t('New email...'),
        ],
      ],
      '#element_required_error' => $this->t('Email is required.'),
      '#element_validate' => [
        ['\Drupal\Core\Render\Element\Email', 'validateEmail'],
        $validate_required,
      ],
      '#skip_ymca_preprocess' => TRUE,
    ];
    $member_id = AnonymousCookieStorage::get('ymca_retention_member');
    if ($member_id && $member = Member::load($member_id)) {
      $form['email_value'] = ['#type' => 'hidden', '#value' => $member->getEmail()];
    }

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Confirm'),
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
        'wrapper' => isset($config['wrapper']) ? $config['wrapper'] : 'registration .registration-form form',
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
        ]
      ],
      '#value' => t('Refresh'),
      '#ajax' => [
        'callback' => [$this, 'ajaxFormRefreshCallback'],
        'event' => 'click',
      ],
    ];

    return $form;
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
    $ajax_response->addCommand(new HtmlCommand('#ymca-retention-user-email-change-form', $new_form));

    return $ajax_response;
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
    if ($form_state->hasAnyErrors()) {
      $form['messages'] = ['#type' => 'status_messages'];
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $member_id = AnonymousCookieStorage::get('ymca_retention_member');
    $query = \Drupal::entityQuery('ymca_retention_member')
      ->condition('id', $member_id);
    $result = $query->execute();
    if (!empty($result)) {
      $entity = $this->updateEntity($member_id, $form_state);
      $this->refreshValues($form_state);
      $form_state->setRebuild();
      drupal_set_message($this->t('Confirmed email address @email.', [
        '@email' => $entity->getEmail(),
      ]));
    }
  }

  /**
   * Update member entity.
   *
   * @param int $entity_id
   *   Entity id.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state.
   *
   * @return \Drupal\ymca_retention\Entity\Member
   *   Member entity.
   */
  protected function updateEntity($entity_id, FormStateInterface $form_state) {
    $entity = Member::load($entity_id);
    // Update member email.
    $entity->setEmail($form_state->getValue('email'));
    $entity->save();

    return $entity;
  }

  /**
   * Refresh values.
   */
  protected function refreshValues(FormStateInterface $form_state) {
    $user_input = $form_state->getUserInput();
    unset($user_input['email']);
    $form_state->setUserInput($user_input);
  }

}
