<?php

namespace Drupal\contact_storage\Tests;

use Drupal\contact\Entity\ContactForm;
use Drupal\field_ui\Tests\FieldUiTestTrait;
use Drupal\language\Entity\ConfigurableLanguage;

/**
 * Tests storing contact messages and viewing them through UI.
 *
 * @group contact_storage
 */
class ContactStorageTest extends ContactStorageTestBase {

  use FieldUiTestTrait;

  /**
   * An administrative user with permission to administer contact forms.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array(
    'text',
    'block',
    'contact',
    'language',
    'field_ui',
    'contact_storage_test',
    'contact_test',
    'contact_storage',
  );

  protected function setUp() {
    parent::setUp();

    $this->drupalPlaceBlock('system_breadcrumb_block');
    $this->drupalPlaceBlock('local_actions_block');
    $this->drupalPlaceBlock('page_title_block');

    // Create and login administrative user.
    $this->adminUser = $this->drupalCreateUser([
      'access site-wide contact form',
      'administer contact forms',
      'administer users',
      'administer account settings',
      'administer contact_message fields',
      'administer contact_message form display',
      'administer contact_message display',
    ]);
    $this->drupalLogin($this->adminUser);
  }

  /**
   * Tests contact messages submitted through contact form.
   */
  public function testContactStorage() {
    // Create first valid contact form.
    $mail = 'simpletest@example.com';
    $this->addContactForm('test_id', 'test_label', $mail, '', TRUE);
    $this->assertText(t('Contact form test_label has been added.'));

    // Ensure that anonymous can submit site-wide contact form.
    user_role_grant_permissions(DRUPAL_ANONYMOUS_RID, array('access site-wide contact form'));
    $this->drupalLogout();
    $this->drupalGet('contact');
    $this->assertText(t('Your email address'));
    $this->assertNoText(t('Form'));
    $this->submitContact('Test_name', $mail, 'Test_subject', 'test_id', 'Test_message');
    $this->assertText(t('Your message has been sent.'));

    // Verify that only 1 message has been sent (by default, the "Send a copy
    // to yourself" option is disabled.
    $captured_emails = $this->drupalGetMails();
    $this->assertTrue(count($captured_emails) === 1);

    // Login as admin.
    $this->drupalLogin($this->adminUser);

    // Verify that the global setting stating whether e-mails should be sent in
    // HTML format is false by default.
    $this->assertFalse(\Drupal::config('contact_storage.settings')->get('send_html'));

    // Verify that this first e-mail was sent in plain text format.
    $captured_emails = $this->drupalGetMails();
    $this->assertTrue(strpos($captured_emails[0]['headers']['Content-Type'], 'text/plain') !== FALSE);

    // Go to the settings form and enable sending messages in HTML format.
    $this->drupalGet('/admin/structure/contact/settings');
    $enable_html = array(
      'send_html' => TRUE,
    );
    $this->drupalPostForm(NULL, $enable_html, t('Save configuration'));

    // Check that the form POST was successful.
    $this->assertText('The configuration options have been saved.');

    // Check that the global setting is properly updated.
    $this->assertTrue(\Drupal::config('contact_storage.settings')->get('send_html'));

    $display_fields = array(
      "The sender's name",
      "The sender's email",
      "Subject",
    );

    // Check that name, subject and mail are configurable on display.
    $this->drupalGet('admin/structure/contact/manage/test_id/display');
    foreach ($display_fields as $label) {
      $this->assertText($label);
    }

    // Check that preview is configurable on form display.
    $this->drupalGet('admin/structure/contact/manage/test_id/form-display');
    $this->assertText('Preview');

    // Check the message list overview.
    $this->drupalGet('admin/structure/contact/messages');
    $rows = $this->xpath('//tbody/tr');
    // Make sure only 1 message is available.
    $this->assertEqual(count($rows), 1);
    // Some fields should be present.
    $this->assertText('Test_subject');
    $this->assertText('Test_name');
    $this->assertText('test_label');

    // Click the view link and make sure name, subject and email are displayed
    // by default.
    $this->clickLink(t('View'));
    foreach ($display_fields as $label) {
      $this->assertText($label);
    }

    // Make sure the stored message is correct.
    $this->drupalGet('admin/structure/contact/messages');
    $this->clickLink(t('Edit'));
    $this->assertFieldById('edit-name', 'Test_name');
    $this->assertFieldById('edit-mail', $mail);
    $this->assertFieldById('edit-subject-0-value', 'Test_subject');
    $this->assertFieldById('edit-message-0-value', 'Test_message');
    // Submit should redirect back to listing.
    $this->drupalPostForm(NULL, array(), t('Save'));
    $this->assertUrl('admin/structure/contact/messages');

    // Delete the message.
    $this->clickLink(t('Delete'));
    $this->drupalPostForm(NULL, NULL, t('Delete'));
    $this->assertRaw(t('The @entity-type %label has been deleted.', [
      // See \Drupal\Core\Entity\EntityDeleteFormTrait::getDeletionMessage().
      '@entity-type' => 'contact message',
      '%label'       => 'Test_subject',
    ]));
    // Make sure no messages are available.
    $this->assertText('There is no Contact message yet.');

    // Fill the "Submit button text" field and assert the form can still be
    // submitted.
    $edit = [
      'contact_storage_submit_text' => 'Submit the form',
      'contact_storage_preview' => FALSE,
    ];
    $this->drupalPostForm('admin/structure/contact/manage/test_id', $edit, t('Save'));
    $edit = [
      'subject[0][value]' => 'Test subject',
      'message[0][value]' => 'Test message',
    ];
    $this->drupalGet('contact');
    $element = $this->cssSelect('#edit-preview');
    // Preview button is hidden.
    $this->assertTrue(empty($element));
    $this->drupalPostForm(NULL, $edit, t('Submit the form'));
    $this->assertText('Your message has been sent.');

    // Add an Options email item field to the form.
    $settings = array('settings[allowed_values]' => "test_key1|test_label1|simpletest1@example.com\ntest_key2|test_label2|simpletest2@example.com");
    $this->fieldUIAddNewField('admin/structure/contact/manage/test_id', 'category', 'Category', 'contact_storage_options_email', $settings);
    // Verify that the new field shows up correctly on the form.
    $this->drupalGet('contact');
    $this->assertText('Category');
    $this->assertOption('edit-field-category', '_none');
    $this->assertOption('edit-field-category', 'test_key1');
    $this->assertOption('edit-field-category', 'test_key2');

    // Send a message using the Options email item field and enable the "Send a
    // copy to yourself" option.
    $captured_emails = $this->drupalGetMails();
    $emails_count_before = count($captured_emails);
    $edit = [
      'subject[0][value]' => 'Test subject',
      'message[0][value]' => 'Test message',
      'field_category' => 'test_key2',
      'copy' => 'checked',
    ];
    $this->drupalPostForm(NULL, $edit, t('Submit the form'));
    $this->assertText('Your message has been sent.');

    // Check that 2 messages were sent and that the body of the last
    // message contains the added message.
    $captured_emails = $this->drupalGetMails();
    $emails_count_after = count($captured_emails);
    $this->assertTrue($emails_count_after === ($emails_count_before + 2));
    $this->assertMailString('body', 'test_key2', 2);

    // The last message is the one sent as a copy, the one before it is the
    // original. Check that the original contains the added recipients and that
    // the copied one is only sent to the sender.
    $logged_in_user_email = $this->loggedInUser->getEmail();
    $this->assertTrue($captured_emails[$emails_count_after - 2]['to'] == "$mail,simpletest2@example.com");
    $this->assertTrue($captured_emails[$emails_count_after - 1]['to'] == $logged_in_user_email);

    // Test clone functionality - add field to existing form.
    $this->fieldUIAddNewField('admin/structure/contact/manage/test_id', 'text_field', 'Text field', 'text');
    // Then clone it.
    $this->drupalGet('admin/structure/contact/manage/test_id/clone');
    $this->drupalPostForm(NULL, [
      'id' => 'test_id_2',
      'label' => 'Cloned',
    ], t('Clone'));

    $edit = [
      'subject[0][value]' => 'Test subject',
      'message[0][value]' => 'Test message',
    ];

    // The added field should be on the cloned form too.
    $edit['field_text_field[0][value]'] = 'Some text';
    $this->drupalGet('contact/test_id_2');
    $this->drupalPostForm(NULL, $edit, t('Submit the form'));
    $form = ContactForm::load('test_id_2');
    $this->assertTrue($form->uuid());

    // Try changing the options email label, field default value and setting it
    // to required.
    $this->drupalGet('/admin/structure/contact/manage/test_id/fields');
    $this->clickLink('Edit');
    $this->drupalPostForm(NULL, [
      'label' => 'Category-2',
      'required' => TRUE,
      'default_value_input[field_category]' => 'test_key1',
    ], t('Save settings'));

    // Verify that the changes are visible into the contact form.
    $this->drupalGet('contact');
    $this->assertText('Category-2');
    $this->assertOption('edit-field-category', 'test_key1');
    $this->assertOption('edit-field-category', 'test_key2');
    $this->assertTrue($this->xpath('//select[@id="edit-field-category" and @required="required"]//option[@value="test_key1" and @selected="selected"]'));

    // Verify that the 'View messages' link exists for the 2 forms and that it
    // links to the correct view.
    $this->drupalGet('/admin/structure/contact');
    $this->assertLinkByHref('/admin/structure/contact/messages?form=test_id');
    $this->assertLinkByHref('/admin/structure/contact/messages?form=test_id_2');

    // Create a new contact form and assert that the disable link exists for
    // each forms.
    $this->addContactForm('test_disable_id', 'test_disable_label', 'simpletest@example.com', '', FALSE);
    $this->drupalGet('/admin/structure/contact');
    $contact_form_count = count(ContactForm::loadMultiple());
    $this->assertEqual(count($this->cssSelect('li.disable a:contains(Disable)')), $contact_form_count);

    // Disable the form and assert that there is 1 less "Disable" button and 1
    // "Enable" button.
    $this->drupalPostForm('/admin/structure/contact/manage/test_disable_id/disable', NULL, t('Disable'));
    $this->assertText('Disabled contact form test_disable_label.');
    $this->drupalGet('/admin/structure/contact');
    $this->assertEqual(count($this->cssSelect('li.disable a:contains(Disable)')), ($contact_form_count - 1));
    $this->assertEqual(count($this->cssSelect('li.enable a:contains(Enable)')), 1);

    // Assert that the disabled form has no input or text area and the message.
    $this->drupalGet('contact/test_disable_id');
    $this->assertEqual(count($this->cssSelect('input')), 0);
    $this->assertEqual(count($this->cssSelect('textarea')), 0);
    $this->assertText('This contact form has been disabled.');

    // Try to re-enable the form and assert that it can be accessed.
    $this->drupalPostForm('/admin/structure/contact/manage/test_disable_id/enable', NULL, t('Enable'));
    $this->assertText('Enabled contact form test_disable_label.');
    $this->drupalGet('contact/test_disable_id');
    $this->assertNoText('This contact form has been disabled.');

    // Create a new contact form with a custom disabled message, disable it and
    // assert that the message displayed is correct.
    $this->addContactForm('test_disable_id_2', 'test_disable_label_2', 'simpletest@example.com', '', FALSE, ['contact_storage_disabled_form_message' => 'custom disabled message']);
    $this->drupalPostForm('/admin/structure/contact/manage/test_disable_id_2/disable', NULL, t('Disable'));
    $this->assertText('Disabled contact form test_disable_label_2.');
    $this->drupalGet('contact/test_disable_id_2');
    $this->assertText('custom disabled message');
  }

  /**
   * Tests the url alias creation feature.
   */
  public function testUrlAlias() {

    // Add a second language to make sure aliases work with any language.
    $language = ConfigurableLanguage::createFromLangcode('de');
    $language->save();

    // Set the second language as default.
    $this->config('system.site')->set('default_langcode', $language->getId())->save();
    $this->rebuildContainer();

    $mail = 'simpletest@example.com';
    // Test for alias without slash.
    $this->addContactForm('form_alias_1', 'contactForm', $mail, '', FALSE, ['contact_storage_url_alias' => 'form51']);
    $this->assertText('The alias path has to start with a slash.');
    $this->drupalGet('form51');
    $this->assertResponse(404);

    // Test for correct alias. Verify that we land on the correct contact form.
    $this->addContactForm('form_alias_2', 'contactForm', $mail, '', FALSE, ['contact_storage_url_alias' => '/form51']);
    $this->assertText('Contact form contactForm has been added.');
    $this->drupalGet('form51');
    $this->assertResponse(200);
    $this->assertText('contactForm');

    // Edit the contact form by changing the alias. Verify that the new alias
    // is generated and the old one removed.
    $this->drupalPostForm('admin/structure/contact/manage/form_alias_2', ['contact_storage_url_alias' => '/form52'], 'Save');
    $this->assertText('Contact form contactForm has been updated.');
    $this->drupalGet('form51');
    $this->assertResponse(404);
    $this->drupalGet('form52');
    $this->assertResponse(200);
    $this->assertText('contactForm');

    // Edit the contact form by removing the alias. Verify that is is deleted.
    $this->drupalPostForm('admin/structure/contact/manage/form_alias_2', ['contact_storage_url_alias' => ''], 'Save');
    $this->assertText('Contact form contactForm has been updated.');
    $this->drupalGet('form52');
    $this->assertResponse(404);

    // Add an alias back and delete the contact form. Verify that the alias is
    // deleted along with the contact form.
    $this->drupalPostForm('admin/structure/contact/manage/form_alias_2', ['contact_storage_url_alias' => '/form52'], 'Save');
    $this->assertText('Contact form contactForm has been updated.');
    $this->drupalGet('form52');
    $this->assertResponse(200);
    $this->assertText('contactForm');
    $this->drupalPostForm('admin/structure/contact/manage/form_alias_2/delete', [], 'Delete');
    $alias = \Drupal::service('path.alias_storage')->load(['source' => '/contact/form_alias_2']);
    $this->assertFalse($alias);
  }

  public function testMaximumSubmissionLimit() {
    // Create a new contact form with a maximum submission limit of 2.
    $this->addContactForm('test_id_3', 'test_label', 'simpletest@example.com', '', FALSE, ['contact_storage_maximum_submissions_user' => 2]);
    $this->assertText(t('Contact form test_label has been added.'));

    // Sends 2 messages with "Send yourself a copy" option activated, shouldn't
    // reach the limit even if 2 messages are sent twice.
    $this->drupalGet('contact/test_id_3');
    $edit = [
      'subject[0][value]' => 'Test subject',
      'message[0][value]' => 'Test message',
      'copy' => 'checked',
    ];
    $this->drupalPostForm(NULL, $edit, t('Send message'));
    $this->assertText(t('Your message has been sent.'));
    $this->drupalGet('contact/test_id_3');
    $this->drupalPostForm(NULL, $edit, t('Send message'));
    $this->assertText(t('Your message has been sent.'));

    // Try accessing the form after the limit has been reached.
    $this->drupalGet('contact/test_id_3');
    $this->assertText(t('You have reached the maximum submission limit of 2 for this form.'));
  }

}
