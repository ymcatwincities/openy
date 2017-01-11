<?php

namespace Drupal\contact_storage\Tests;

use Drupal\Component\Utility\SafeMarkup;
use Drupal\views\Tests\ViewTestData;

/**
 * Tests a contact message bulk form.
 *
 * @group contact_storage
 * @see \Drupal\contact_storage\Plugin\views\field\MessageBulkForm
 */
class BulkFormTest extends ContactStorageTestBase {

  /**
   * Modules to be enabled.
   *
   * @var array
   */
  public static $modules = array(
    'contact_storage',
    'contact_test_views',
    'language',
  );

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = array('test_contact_message_bulk_form');

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::Setup();
    // Create and login administrative user.
    $admin_user = $this->drupalCreateUser(array(
      'administer contact forms',
    ));
    $this->drupalLogin($admin_user);
    // Create first valid contact form.
    $mail = 'simpletest@example.com';
    $this->addContactForm('test_id', 'test_label', $mail, '', TRUE);
    $this->assertText(t('Contact form test_label has been added.'));
    $this->drupalLogout();

    // Ensure that anonymous can submit site-wide contact form.
    user_role_grant_permissions(DRUPAL_ANONYMOUS_RID, array('access site-wide contact form'));
    $this->drupalGet('contact');
    $this->assertText(t('Your email address'));
    // Submit contact form few times.
    for ($i = 1; $i <= 5; $i++) {
      $this->submitContact($this->randomMachineName(), $mail, $this->randomMachineName(), 'test_id', $this->randomMachineName());
      $this->assertText(t('Your message has been sent.'));
    }
  }

  /**
   * Test multiple deletion.
   */
  public function testBulkDeletion() {
    $this->drupalGet('contact');
    ViewTestData::createTestViews(get_class($this), array('contact_test_views'));
    // Check the operations are accessible to the administer permission user.
    $this->drupalLogin($this->drupalCreateUser(array('administer contact forms')));
    $this->drupalGet('test-contact-message-bulk-form');
    $elements = $this->xpath('//select[@id="edit-action"]//option');
    $this->assertIdentical(count($elements), 1, 'All contact message operations are found.');
    $this->drupalPostForm('test-contact-message-bulk-form', [], t('Apply to selected items'));
    $this->assertText(t('No message selected.'));
  }

}
