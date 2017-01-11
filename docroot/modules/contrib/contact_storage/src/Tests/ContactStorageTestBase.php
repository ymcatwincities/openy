<?php

namespace Drupal\contact_storage\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Defines a base-class for contact-storage tests.
 */
abstract class ContactStorageTestBase extends WebTestBase {

  /**
   * Adds a form.
   *
   * @param string $id
   *   The form machine name.
   * @param string $label
   *   The form label.
   * @param string $recipients
   *   The list of recipient email addresses.
   * @param string $reply
   *   The auto-reply text that is sent to a user upon completing the contact
   *   form.
   * @param bool $selected
   *   A Boolean indicating whether the form should be selected by default.
   * @param array $third_party_settings
   *   Array of third party settings to be added to the posted form data.
   * @param string $message
   *   The message that will be displayed to a user upon completing the contact
   *   form.
   */
  function addContactForm($id, $label, $recipients, $reply, $selected, $third_party_settings = [], $message = 'Your message has been sent.') {
    $this->drupalGet('admin/structure/contact/add');
    $edit = [];
    $edit['label'] = $label;
    $edit['id'] = $id;
    // 8.2.x added the message field, which is by default empty. Conditionally
    // submit it if the field can be found.
    if ($this->xpath($this->constructFieldXpath('name', 'message'))) {
      $edit['message'] = $message;
    }
    $edit['recipients'] = $recipients;
    $edit['reply'] = $reply;
    $edit['selected'] = ($selected ? TRUE : FALSE);
    $edit += $third_party_settings;
    $this->drupalPostForm(NULL, $edit, t('Save'));
  }

  /**
   * Submits the contact form.
   *
   * @param string $name
   *   The name of the sender.
   * @param string $mail
   *   The email address of the sender.
   * @param string $subject
   *   The subject of the message.
   * @param string $id
   *   The form ID of the message.
   * @param string $message
   *   The message body.
   */
  public function submitContact($name, $mail, $subject, $id, $message) {
    $edit = [];
    $edit['name'] = $name;
    $edit['mail'] = $mail;
    $edit['subject[0][value]'] = $subject;
    $edit['message[0][value]'] = $message;
    if ($id == $this->config('contact.settings')->get('default_form')) {
      $this->drupalPostForm('contact', $edit, t('Send message'));
    }
    else {
      $this->drupalPostForm('contact/' . $id, $edit, t('Send message'));
    }
  }

}
