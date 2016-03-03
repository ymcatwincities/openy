<?php

namespace Drupal\ymca_membership;

use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\contact\MailHandler;
use Drupal\contact\MailHandlerInterface;
use Drupal\contact\MessageInterface;

/**
 * Provides a class for handling assembly and dispatch of contact mail messages.
 */
class YmcaMembershipMailHandler extends MailHandler implements MailHandlerInterface {

  /**
   * {@inheritdoc}
   */
  public function sendMailMessages(MessageInterface $message, AccountInterface $sender) {
    // Override only membership form mails sending behavior.
    if ($message->bundle() !== 'membership_form') {
      parent::sendMailMessages($message, $sender);
      return;
    }

    // Send a mail to the person, who filled the form.
    $message->set('copy', TRUE);
    $sender_name = $message->field_first_name->getValue()[0]['value'] . ' ' . $message->field_last_name->getValue()[0]['value'];
    $message->setSenderName($sender_name);
    $message->setSenderMail($message->field_email_address->getValue()[0]['value']);
    $message->setSubject('YMCA Membership Inquiry');
    $message->save();
    $anonymous = $this->userStorage->load(0);
    // Replace $sender with anonymous account.
    parent::sendMailMessages($message, $anonymous);
  }

}
