<?php
/**
 * @file
 * Contains \Drupal\mailsystem_test\Controller\MailsystemTestController.
 */

namespace Drupal\mailsystem_test\Controller;
use Symfony\Component\HttpFoundation\Response;

/**
 * A mailsystem test controller for use by tests in this file.
 */
class MailsystemTestController {

  /**
   * Composes and optionally sends an email message.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   */
  public function sendMail() {
    $module = 'mailsystem_test';
    $key = 'theme_test';
    $to = 'theme_test@example.com';
    $langcode = \Drupal::languageManager()->getDefaultLanguage()->getId();
    \Drupal::service('plugin.manager.mail')->mail($module, $key, $to, $langcode);
    return new Response('', 204);
  }
}
