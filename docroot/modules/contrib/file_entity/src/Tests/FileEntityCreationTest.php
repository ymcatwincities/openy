<?php

/**
 * @file
 * Contains \Drupal\file_entity\Tests\FileEntityCreationTest.
 */

namespace Drupal\file_entity\Tests;

use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Site\Settings;
use Drupal\file_entity\Entity\FileEntity;

/**
 * Tests creating and saving a file.
 *
 * @group file_entity
 */
class FileEntityCreationTest extends FileEntityTestBase {

  public static $modules = array('views');

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $web_user = $this->drupalCreateUser(array('create files',
      'edit own document files',
      'administer files',
      'administer site configuration',
      'view private files',
    ));
    $this->drupalLogin($web_user);
  }

  /**
   * Create a "document" file and verify its consistency in the database.
   *
   * Unset the private folder so it skips the scheme selecting page.
   */
  public function testSingleFileEntityCreation() {
    // Configure private file system path.
    // Unset private file system path so it skips the scheme selecting, because
    // there is only one file system path available (public://) by default.
    new Settings(['file_private_path' => NULL] + Settings::getAll());
    $this->rebuildContainer();

    $test_file = $this->getTestFile('text');
    // Create a file.
    $edit = array();
    $edit['files[upload]'] = drupal_realpath($test_file->uri);
    $this->drupalPostForm('file/add', $edit, t('Next'));

    // Check that the document file has been uploaded.
    $this->assertRaw(t('@type %name was uploaded.', array('@type' => 'Document', '%name' => $test_file->filename)), t('Document file uploaded.'));

    // Check that the file exists in the database.
    $file = $this->getFileByFilename($test_file->filename);
    $this->assertTrue($file, t('File found in database.'));
  }

  /**
   * Upload a file with both private and public folder set.
   *
   * Should have one extra step selecting a scheme.
   * Selects private scheme and checks if the file is succesfully uploaded to
   * the private folder.
   */
  public function testFileEntityCreationMultipleSteps() {
    $test_file = $this->getTestFile('text');
    // Create a file.
    $edit = array();
    $edit['files[upload]'] = drupal_realpath($test_file->uri);
    $this->drupalGet('file/add');
    $this->assertFalse($this->xpath('//input[@id="edit-upload-remove-button"]'), 'Remove');
    $this->drupalPostForm(NULL, $edit, t('Next'));

    // Check if your on form step 2, scheme selecting.
    // At this point it should not skip this form.
    $this->assertTrue($this->xpath('//input[@name="scheme"]'), "Loaded select destination scheme page.");

    // Test if the public radio button is selected by default.
    $this->assertFieldChecked('edit-scheme-public', 'Public Scheme is checked');

    // Submit form and set scheme to private.
    $edit = array();
    $edit['scheme'] = 'private';
    $this->drupalPostForm(NULL, $edit, t('Next'));

    // Check that the document file has been uploaded.
    $this->assertRaw(t('@type %name was uploaded.', array('@type' => 'Document', '%name' => $test_file->filename)), t('Document file uploaded.'));

    // Check that the file exists in the database.
    $file = $this->getFileByFilename($test_file->filename);
    $this->assertTrue($file, t('File found in database.'));

    // Check if the file is stored in the private folder.
    $this->assertTrue(substr($file->getFileUri(), 0, 10) === 'private://', 'File uploaded in private folder.');
  }

  /**
   * Test the Title Text and Alt Text fields of to the predefined Image type.
   */
  public function testImageAltTitleFields() {
    // Disable private path to avoid irrelevant second form step.
    new Settings(['file_private_path' => NULL] + Settings::getAll());
    $this->rebuildContainer();

    // Create an image.
    $test_file = $this->getTestFile('image');
    $edit = array('files[upload]' => drupal_realpath($test_file->uri));
    $this->drupalPostForm('file/add', $edit, t('Next'));

    $data = array(
      'field_image_title_text' => 'My image',
      'field_image_alt_text' => 'A test image',
    );

    // Find the alt and title fields on the next step.
    foreach ($data as $field => $value) {
      $this->assertFieldByXPath('//input[@name="' . $field . '[0][value]"]');
    }

    // Set fields.
    $edit = array();
    foreach ($data as $field => $value) {
      $edit[$field . '[0][value]'] = $value;
    }
    $this->drupalPostForm(NULL, $edit, t('Save'));

    // Make sure the field values are saved.
    $created_file = FileEntity::load(1)->getTranslation(LanguageInterface::LANGCODE_DEFAULT);
    foreach ($data as $field => $value) {
      $this->assertEqual($value, $created_file->get($field)->value);
    }
  }
}
