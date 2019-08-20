<?php

namespace Drupal\myy_personify\Plugin\MyYAuthenticator;

use Drupal\openy_myy\PluginManager\MyYAuthenticatorInterface;

/**
 * Personify instance of Authenticator plugin.
 *
 * @MyYAuthenticator(
 *   id = "myy_personify_authenticator",
 *   label = "MyY Authenticator: Personify",
 *   description = "Authentication logic for Personify integration"
 * )
 */
class PersonifyAuthenticator implements MyYAuthenticatorInterface {

  /**
   * {@inheritdoc}
   */
  public function getUserId() {
    // TODO: Implement getUserId() method.
  }

  /**
   * {@inheritdoc}
   */
  public function loginPage() {
    // TODO: Implement loginPage() method.
  }

  /**
   * {@inheritdoc}
   */
  public function logoutPage() {
    // TODO: Implement logoutPage() method.
  }

}