<?php

namespace Drupal\ymca_retention\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Class Membership.
 *
 * @package Drupal\ymca_retention\Controller
 */
class Membership extends ControllerBase {

  /**
   * Get a registration form.
   *
   * @return array
   *   Render array.
   */
  public function getRegistrationForm() {
    $form = \Drupal::formBuilder()
      ->getForm('\Drupal\ymca_retention\Form\MemberRegisterForm');
    return [
      '#theme' => ['ymca_retention_registration_form'],
      'form' => $form,
    ];
  }

  /**
   * Success message after registering the account.
   *
   * @return array
   *   Render array.
   */
  public function enrollSuccess() {
    $markup = '<div class="success">' . t('Thank you for participating in <br/><strong>Get For The Gold</strong>! <br/>We look forward to see you in the upcoming weeks.') . '</div>';
    return [
      '#markup' => $markup,
    ];
  }

}
