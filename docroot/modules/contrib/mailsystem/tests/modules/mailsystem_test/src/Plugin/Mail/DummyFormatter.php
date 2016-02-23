<?php
/**
 * @file
 * Contains \Drupal\mailsystem_test\Plugin\Mail\DummyFormatter.
 */

namespace Drupal\mailsystem_test\Plugin\Mail;

use Drupal\Core\Mail\MailInterface;

/**
 * Provides a 'Dummy' plugin to format emails.
 *
 * @Mail(
 *   id = "mailsystem_dummyformatter",
 *   label = @Translation("Dummy Mailsystem formatter Plugin"),
 *   description = @Translation("Dummy Plugin to debug the email on formatting ,does not sending anything.")
 * )
 */
class DummyFormatter implements MailInterface {

  /**
   * {@inheritdoc}
   */
  public function format(array $message) {
    // TODO: Implement format() method.
    \debug(array(
      'Subject' => $message['subject'],
      'Body' => $message['body'],
      'Headers' => $message['headers'],
    ), 'DummyFormatter: format()');
    return $message;
  }

  /**
   * {@inheritdoc}
   */
  public function mail(array $message) {
    return FALSE;
  }

}
