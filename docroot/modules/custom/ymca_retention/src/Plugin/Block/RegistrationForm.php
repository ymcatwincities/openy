<?php

namespace Drupal\ymca_retention\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\ymca_retention\AnonymousCookieStorage;

/**
 * Provides a block with registration form.
 *
 * @Block(
 *   id = "retention_registration_block",
 *   admin_label = @Translation("YMCA retention registration block"),
 *   category = @Translation("YMCA Blocks")
 * )
 */
class RegistrationForm extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    // Remove cookie in case when registration form is displayed on the page.
    AnonymousCookieStorage::delete('ymca_retention_member');
    $form = \Drupal::formBuilder()
      ->getForm('\Drupal\ymca_retention\Form\MemberRegisterForm');
    return [
      '#theme' => 'ymca_retention_registration_form',
      'form' => $form,
    ];
  }

}
