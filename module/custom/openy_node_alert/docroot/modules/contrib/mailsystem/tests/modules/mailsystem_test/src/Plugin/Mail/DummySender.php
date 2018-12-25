<?php
/**
 * @file
 * Contains \Drupal\mailsystem_test\Plugin\Mail\DummySender.
 */

namespace Drupal\mailsystem_test\Plugin\Mail;

use Drupal\Core\Mail\MailInterface;

/**
 * Provides a 'Dummy' plugin to format emails.
 *
 * @Mail(
 *   id = "mailsystem_dummysender",
 *   label = @Translation("Dummy Mailsystem sender Plugin"),
 *   description = @Translation("Dummy Plugin to debug the email instead of sending and does nothing on formatting.")
 * )
 */
class DummySender implements MailInterface {
  /**
   * {@inheritdoc}
   */
  public function format(array $message) {
    return $message;
  }

  /**
   * {@inheritdoc}
   */
  public function mail(array $message) {
    // TODO: Implement format() method.
    \debug(array(
      'Subject' => $message['subject'],
      'Body' => $message['body'],
      'Headers' => $message['headers'],
    ), 'DummySender: mail()');
    return $message;
  }

}
