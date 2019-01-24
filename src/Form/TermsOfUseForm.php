<?php

namespace Drupal\openy\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a form to configure and rewrite settings.php.
 */
class TermsOfUseForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'openy_terms_of_use';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = \Drupal::config('openy.terms_and_conditions.schema');
    $isAccepted = $config->get('accepted_version');

    $form['#title'] = $this->t('Terms of Use');

    $form['participant'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('We agree to the <a target="_blank" href="@openy-participant-agreement">Open Y Participant Agreement</a> and <a target="_blank" href="@terms-of-use">Terms of Use</a>', [
        '@openy-participant-agreement' => 'https://github.com/ymcatwincities/openy/wiki/Open-Y-Participant-Agreement',
        '@terms-of-use' => 'https://github.com/ymcatwincities/openy/wiki/Open-Y-Terms-of-Use',
      ]),
      '#default_value' => ($isAccepted) ? 1 : 0,
      '#weight' => 1,
    ];

    $form['llc'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Open Y, LLC is a separate entity established by YMCA of the Greater Twin Cities to support and amplify digital collaboration among YMCA associations. YUSA supports the Open Y platform with respect to use by its Member Associations but is not responsible for and does not control the services provided by Open Y, LLC.'),
      '#default_value' => ($isAccepted) ? 1 : 0,
      '#weight' => 2,
    ];

    $form['privacy'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Open Y recommends that each participating YMCA association develop and implement its own cybersecurity policies and obtain cyber liability and data privacy insurance.'),
      '#default_value' => ($isAccepted) ? 1 : 0,
      '#weight' => 3,
    ];

    $form['acknowledge'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('I acknowledge that Open Y is open source content and that all content is provided “as is” without any warranty of any kind. Open Y makes no warranty that its services will meet your requirements, be safe, secure, uninterrupted, timely, accurate, or error-free, or that your information will be secure. Open Y will not maintain and support Open Y templates indefinitely. The entire risk as to the quality and performance of the content is with you.'),
      '#default_value' => ($isAccepted) ? 1 : 0,
      '#weight' => 4,
    ];

    $form['obtaining'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Open Y recommends obtaining a reputable agency to assist with the implementation of the Open Y platform and further development for your specific needs.'),
      '#default_value' => ($isAccepted) ? 1 : 0,
      '#weight' => 5,
    ];

    $form['agree_openy_terms'] = [
      '#type' => 'hidden',
      '#weight' => 6,
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Continue Installation'),
      '#weight' => 15,
      '#states' => [
        'disabled' => [
          [':input[name="participant"]' => ['checked' => FALSE]],
          'and',
          [':input[name="llc"]' => ['checked' => FALSE]],
          'and',
          [':input[name="privacy"]' => ['checked' => FALSE]],
          'and',
          [':input[name="acknowledge"]' => ['checked' => FALSE]],
          'and',
          [':input[name="obtaining"]' => ['checked' => FALSE]],
        ],
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $route_name = \Drupal::routeMatch()->getRouteName();
    // If site installation is not run via drush.
    if (!$form_state->getValue('agree_openy_terms')) {
      $values = $form_state->getValues();
      foreach ($values as $key => $value) {
        if ($value === 0) {
          $form_state->setErrorByName($key, $this->t('Select all checkboxes to indicate that you have read and agree to the terms of use'));
        }
      }
    }

    if ($route_name == 'openy_system.openy_terms_and_conditions') {
      $current_user = \Drupal::currentUser();

      if (!in_array('administrator', $current_user->getRoles()) && $current_user->id() != 1) {
        $form_state->setErrorByName(
          'submit',
          $this->t('You do not have the role Administration')
        );
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = \Drupal::configFactory()
      ->getEditable('openy.terms_and_conditions.schema');
    $config->set('version', '2.0');
    $config->set('accepted_version', time());
    $config->save();
  }

}
