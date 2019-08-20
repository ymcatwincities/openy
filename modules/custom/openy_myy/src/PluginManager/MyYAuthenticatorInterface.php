<?php

namespace Drupal\openy_myy\PluginManager;

/**
 * Interface MyYAuthenticatorInterface
 *
 * @package Drupal\openy_myy\PluginManager
 */
interface MyYAuthenticatorInterface {

  /**
   * Renders user login page or redirect to SSO log in page (used at controller).
   *
   * @return mixed
   */
  public function loginPage();

  /**
   * Implementation of user log out scenario (used at controller).
   *
   * @return mixed
   */
  public function logoutPage();

  /**
   * @return mixed
   */
  public function getUserId();

}
