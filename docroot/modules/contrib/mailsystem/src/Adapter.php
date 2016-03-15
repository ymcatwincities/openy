<?php
/**
 * @file
 * Contains \Drupal\mailsystem\Adapter.
 */

namespace Drupal\mailsystem;

use Drupal\Core\Mail\MailInterface;

/**
 * Provides an adapter to send emails.
 */
class Adapter implements MailInterface {

  /**
   * @var \Drupal\Core\Mail\MailInterface
   */
  protected $instanceFormatter;

  /**
   * @var \Drupal\Core\Mail\MailInterface
   */
  protected $instanceSender;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Mail\MailInterface $formatter
   *   The MailPlugin for formatting the email before sending.
   * @param \Drupal\Core\Mail\MailInterface $sender
   *   The MailPlugin for sending the email.
   */
  public function __construct(MailInterface $formatter, MailInterface $sender) {
    $this->instanceFormatter = $formatter;
    $this->instanceSender = $sender;
  }

  /**
   * {@inheritdoc}
   */
  public function format(array $message) {
    return $this->instanceFormatter->format($message);
  }

  /**
   * {@inheritdoc}
   */
  public function mail(array $message) {
    return $this->instanceSender->mail($message);
  }

}
