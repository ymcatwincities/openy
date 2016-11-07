<?php

namespace Drupal\ymca_retention\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a user menu block.
 *
 * @Block(
 *   id = "retention_user_menu_block",
 *   admin_label = @Translation("[YMCA Retention] User menu"),
 *   category = @Translation("YMCA Blocks")
 * )
 */
class UserMenu extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $config = [
      'theme' => 'ymca_retention_login_form_modal',
      'wrapper' => 'ymca-retention-user-menu-login-form form',
    ];
    $login_form = \Drupal::formBuilder()
      ->getForm('\Drupal\ymca_retention\Form\MemberLoginForm', $config);
    $config = [
      'yteam' => 0,
      'theme' => 'ymca_retention_register_form_modal',
      'wrapper' => 'ymca-retention-user-menu-register-form form',
    ];
    $register_form = \Drupal::formBuilder()
      ->getForm('\Drupal\ymca_retention\Form\MemberRegisterForm', $config);

    return [
      '#theme' => 'ymca_retention_user_menu',
      '#content' => [
        'login_form' => $login_form,
        'register_form' => $register_form,
      ],
      '#attached' => [
        'library' => [
          'ymca_retention/user-menu',
        ],
      ],
    ];
  }

}
