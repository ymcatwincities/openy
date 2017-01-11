<?php

namespace Drupal\contact_storage\Tests;

/**
 * Tests adding contact form as entity reference and viewing them through UI.
 *
 * @group contact_storage
 */
class ContactViewBuilderTest extends ContactStorageTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'user',
    'node',
    'contact',
    'field_ui',
    'contact_test',
    'contact_storage',
  ];

  /**
   * An administrative user with permission administer contact forms.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Create Article node type.
    $this->drupalCreateContentType([
      'type' => 'article',
      'name' => 'Article',
      'display_submitted' => FALSE,
    ]);
  }

  /**
   * Tests contact view builder functionality.
   */
  public function testContactViewBuilder() {
    // Create test admin user.
    $this->adminUser = $this->drupalCreateUser([
      'administer content types',
      'access site-wide contact form',
      'administer contact forms',
      'administer users',
      'administer account settings',
      'administer contact_message fields',
    ]);

    // Login as admin user.
    $this->drupalLogin($this->adminUser);

    // Create first valid contact form.
    $mail = 'simpletest@example.com';
    $this->addContactForm('test_id', 'test_label', $mail, '', TRUE);
    $this->assertText(t('Contact form test_label has been added.'));

    $field_name = 'contact';
    $entity_type = 'node';
    $bundle_name = 'article';

    // Add a Entity Reference Contact Field to Article content type.
    $field_storage = \Drupal::entityManager()
      ->getStorage('field_storage_config')
      ->create([
        'field_name' => $field_name,
        'entity_type' => $entity_type,
        'type' => 'entity_reference',
        'settings' => ['target_type' => 'contact_form'],
      ]);
    $field_storage->save();
    $field = \Drupal::entityManager()
      ->getStorage('field_config')
      ->create([
        'field_storage' => $field_storage,
        'bundle' => $bundle_name,
        'settings' => [
          'handler' => 'default',
        ],
      ]);
    $field->save();

    // Configure the contact reference field form Entity form display.
    entity_get_form_display($entity_type, $bundle_name, 'default')
      ->setComponent($field_name, [
        'type' => 'options_select',
        'settings' => [
          'weight' => 20,
        ],
      ])
      ->save();

    // Configure the contact reference field form Entity view display.
    entity_get_display('node', 'article', 'default')
      ->setComponent($field_name, [
        'label' => 'above',
        'type' => 'entity_reference_entity_view',
        'weight' => 20,
      ])
      ->save();

    // Display Article creation form.
    $this->drupalGet('node/add/article');
    $title_key = 'title[0][value]';
    $body_key = 'body[0][value]';
    $contact_key = 'contact';
    // Create article node.
    $edit = [];
    $edit[$title_key] = $this->randomMachineName(8);
    $edit[$body_key] = $this->randomMachineName(16);
    $edit[$contact_key] = 'test_id';
    $this->drupalPostForm('node/add/article', $edit, t('Save'));
    // Check that the node exists in the database.
    $node = $this->drupalGetNodeByTitle($edit[$title_key]);
    $this->drupalGet('node/' . $node->id());
    // Some fields should be present.
    $this->assertText(t('Your email address'));
    $this->assertText(t('Subject'));
    $this->assertText(t('Message'));
    $this->assertFieldByName('subject[0][value]');
    $this->assertFieldByName('message[0][value]');
  }

}
