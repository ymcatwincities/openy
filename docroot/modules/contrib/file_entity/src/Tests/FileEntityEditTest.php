<?php

namespace Drupal\file_entity\Tests;
use Drupal\Core\Url;
use Drupal\file\Entity\File;

/**
 * Create a file and test file edit functionality.
 *
 * @group file_entity
 */
class FileEntityEditTest extends FileEntityTestBase {
  protected $web_user;
  protected $admin_user;

  public static $modules = ['block'];

  function setUp() {
    parent::setUp();
    // Add the tasks and actions blocks.
    $this->drupalPlaceBlock('local_actions_block');
    $this->drupalPlaceBlock('local_tasks_block');

    $this->web_user = $this->drupalCreateUser(array('edit own document files', 'create files'));
    $this->admin_user = $this->drupalCreateUser(array('bypass file access', 'administer files'));
  }

  /**
   * Check file edit functionality.
   */
  function testFileEntityEdit() {
    $this->drupalLogin($this->web_user);

    $test_file = $this->getTestFile('text');
    $name_key = "filename[0][value]";

    // Create file to edit.
    $edit = array();
    $edit['files[upload]'] = drupal_realpath($test_file->uri);
    $this->drupalPostForm('file/add', $edit, t('Next'));
    if ($this->xpath('//input[@name="scheme"]')) {
      $this->drupalPostForm(NULL, array(), t('Next'));
    }

    // Check that the file exists in the database.
    $file = $this->getFileByFilename($test_file->filename);
    $this->assertTrue($file, t('File found in database.'));

    // Check that "edit" link points to correct page.
    $this->clickLink(t('Edit'));
    $edit_url = $file->url('edit-form', ['absolute' => TRUE]);
    $actual_url = $this->getURL();
    $this->assertEqual($edit_url, $actual_url, t('On edit page.'));

    // Check that the name field is displayed with the correct value.
    $active = t('(active tab)');
    $link_text = t('@local-task-title<span class="element-invisible">@active</span>', array('@local-task-title' => t('Edit'), '@active' => $active));
    $this->assertText(strip_tags($link_text), 0, t('Edit tab found and marked active.'));
    $this->assertFieldByName($name_key, $file->label(), t('Name field displayed.'));

    // The user does not have "delete" permissions so no delete button should be found.
    $this->assertNoFieldByName('op', t('Delete'), 'Delete button not found.');

    // Edit the content of the file.
    $edit = array();
    $edit[$name_key] = $this->randomMachineName(8);
    // Stay on the current page, without reloading.
    $this->drupalPostForm(NULL, $edit, t('Save'));

    // Check that the name field is displayed with the updated values.
    $this->assertText($edit[$name_key], t('Name displayed.'));
  }

  /**
   * Check changing file associated user fields.
   */
  function testFileEntityAssociatedUser() {
    $this->drupalLogin($this->admin_user);

    // Create file to edit.
    $test_file = $this->getTestFile('text');
    $name_key = "filename[0][value]";
    $edit = array();
    $edit['files[upload]'] = drupal_realpath($test_file->uri);
    $this->drupalPostForm('file/add', $edit, t('Next'));

    // Check that the file was associated with the currently logged in user.
    $file = $this->getFileByFilename($test_file->filename);
    $this->assertIdentical($file->getOwnerId(), $this->admin_user->id(), 'File associated with admin user.');

    // Try to change the 'associated user' field to an invalid user name.
    $edit = array(
      'uid[0][target_id]' => 'invalid-name',
    );
    $this->drupalPostForm('file/' . $file->id() . '/edit', $edit, t('Save'));
    $this->assertText('There are no entities matching "invalid-name".');

    // Change the associated user field to the anonymous user (uid 0).
    $edit = array();
    $edit['uid[0][target_id]'] = 'Anonymous (0)';
    $this->drupalPostForm('file/' . $file->id() . '/edit', $edit, t('Save'));
    \Drupal::entityManager()->getStorage('file')->resetCache();
    $file = File::load($file->id());
    $this->assertIdentical($file->getOwnerId(), '0', 'File associated with anonymous user.');

    // Change the associated user field to another user's name (that is not
    // logged in).
    $edit = array();
    $edit['uid[0][target_id]'] = $this->web_user->label();
    $this->drupalPostForm('file/' . $file->id() . '/edit', $edit, t('Save'));
    \Drupal::entityManager()->getStorage('file')->resetCache();
    $file = File::load($file->id());
    $this->assertIdentical($file->getOwnerId(), $this->web_user->id(), 'File associated with normal user.');

    // Check that normal users cannot change the associated user information.
    $this->drupalLogin($this->web_user);
    $this->drupalGet('file/' . $file->id() . '/edit');
    $this->assertNoFieldByName('uid[0][target_id]');
  }
}
