<?php

namespace Drupal\file_entity\Tests;

use Drupal\Component\Utility\Unicode;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\file_entity\Entity\FileType;

/**
 * Tests the file entity types.
 *
 * @group file_entity
 */
class FileEntityTypeTest extends FileEntityTestBase {

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->setUpFiles();
  }

  /**
   * Test admin pages access and functionality.
   */
  public function testAdminPages() {
    // Create a user with file type administration access.
    $user = $this->drupalCreateUser(array('administer file types'));
    $this->drupalLogin($user);

    $this->drupalGet('admin/structure/file-types');
    $this->assertResponse(200, 'File types admin page is accessible');
  }

  /**
   * Test creating a new type. Basic CRUD.
   */
  public function testCreate() {
    $type_machine_type = 'foo';
    $type_machine_label = 'foobar';
    $this->createFileType(array('id' => $type_machine_type, 'label' => $type_machine_label));
    $loaded_type = FileType::load($type_machine_type);
    $this->assertEqual($loaded_type->label(), $type_machine_label, "Was able to create a type and retreive it.");
  }

  /**
   * Make sure candidates are presented in the case of multiple file types.
   */
  public function testTypeWithCandidates() {
    // Create multiple file types with the same mime types.
    $types = array(
      'image1' => $this->createFileType(array('id' => 'image1', 'label' => 'Image 1')),
      'image2' => $this->createFileType(array('id' => 'image2', 'label' => 'Image 2')),
    );

    // Attach a text field to one of the file types.
    $field_name = Unicode::strtolower($this->randomMachineName());
    $field_storage = FieldStorageConfig::create(array(
      'field_name' => $field_name,
      'entity_type' => 'file',
      'type' => 'string',
    ));
    $field_storage->save();
    $field_instance = FieldConfig::create(array(
      'field_storage' => $field_storage,
      'entity_type' => 'file',
      'bundle' => 'image2',
    ));
    $field_instance->save();
    entity_get_form_display('file', 'image2', 'default')
      ->setComponent($field_name, array(
        'type' => 'text_textfield',
      ))
      ->save();


    // Create a user with file creation access.
    $user = $this->drupalCreateUser(array('create files'));
    $this->drupalLogin($user);

    // Step 1: Upload file.
    $file = reset($this->files['image']);
    $edit = array();
    $edit['files[upload]'] = drupal_realpath($file->getFileUri());
    $this->drupalPostForm('file/add', $edit, t('Next'));

    // Step 2: Select file type candidate.
    $this->assertText('Image 1');
    $this->assertText('Image 2');
    $edit = array();
    $edit['type'] = 'image2';
    $this->drupalPostForm(NULL, $edit, t('Next'));

    // Step 3: Select file scheme candidate.
    $this->assertText('Public local files served by the webserver.');
    $this->assertText('Private local files served by Drupal.');
    $edit = array();
    $edit['scheme'] = 'public';
    $this->drupalPostForm(NULL, $edit, t('Next'));

    // Step 4: Complete field widgets.
    $edit = array();
    $edit["{$field_name}[0][value]"] = $this->randomMachineName();
    $edit['filename[0][value]'] = $this->randomMachineName();
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertRaw(t('@type %name was uploaded.', array('@type' => 'Image 2', '%name' => $edit['filename[0][value]'])));

    // Check that the file exists in the database.
    $file = $this->getFileByFilename($edit['filename[0][value]']);
    $this->assertTrue($file, t('File found in database.'));

    // Checks if configurable field exists in the database.
    $this->assertTrue($file->hasField($field_name), 'Found configurable field in database');
  }

  /**
   * Make sure no candidates appear when only one mime type is available.
   */
  public function testTypeWithoutCandidates() {
    // Attach a text field to the default image file type.
    $field_name = Unicode::strtolower($this->randomMachineName());
    $field_storage = FieldStorageConfig::create(array(
      'field_name' => $field_name,
      'entity_type' => 'file',
      'type' => 'string',
    ));
    $field_storage->save();
    $field_instance = FieldConfig::create(array(
      'field_storage' => $field_storage,
      'entity_type' => 'file',
      'bundle' => 'image',
    ));
    $field_instance->save();
    entity_get_form_display('file', 'image', 'default')
      ->setComponent($field_name, array(
      'type' => 'text_textfield',
      ))
      ->save();

    // Create a user with file creation access.
    $user = $this->drupalCreateUser(array('create files'));
    $this->drupalLogin($user);

    // Step 1: Upload file.
    $file = reset($this->files['image']);
    $edit = array();
    $edit['files[upload]'] = drupal_realpath($file->getFileUri());
    $this->drupalPostForm('file/add', $edit, t('Next'));

    // Step 2: Scheme selection.
    if ($this->xpath('//input[@name="scheme"]')) {
      $this->drupalPostForm(NULL, array(), t('Next'));
    }

    // Step 3: Complete field widgets.
    $edit = array();
    $edit["{$field_name}[0][value]"] = $this->randomMachineName();
    $edit['filename[0][value]'] = $this->randomMachineName();
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertRaw(t('@type %name was uploaded.', array('@type' => 'Image', '%name' => $edit['filename[0][value]'])));

    // Check that the file exists in the database.
    $file = $this->getFileByFilename($edit['filename[0][value]']);
    $this->assertTrue($file, t('File found in database.'));

    // Checks if configurable field exists in the database.
    $this->assertTrue($file->hasField($field_name), 'Found configurable field in database');
  }

  /**
   * Test file types CRUD UI.
   */
  public function testTypesCrudUi() {
    $this->drupalGet('admin/structure/file-types');
    $this->assertResponse(403);

    $user = $this->drupalCreateUser(array('administer file types'));
    $this->drupalLogin($user);

    $this->drupalGet('admin/structure/file-types');
    $this->assertResponse(200);

    // Create new file type.
    $edit = array(
      'label' => t('Test type'),
      'id' => 'test_type',
      'description' => t('This is dummy file type used just for testing.'),
      'mimetypes' => 'image/png',
    );
    $this->drupalGet('admin/structure/file-types/add');
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertText(t('The file type @type has been added.', array('@type' => $edit['label'])));
    $this->assertText($edit['label']);
    $this->assertText($edit['description']);
    $this->assertLink(t('Disable'));
    $this->assertLink(t('Delete'));
    $this->assertLinkByHref('admin/structure/file-types/manage/' . $edit['id'] . '/disable');
    $this->assertLinkByHref('admin/structure/file-types/manage/' . $edit['id'] . '/delete');

    // Edit file type.
    $this->drupalGet('admin/structure/file-types/manage/' . $edit['id'] . '/edit');
    $this->assertRaw(t('Save'));
    $this->assertRaw(t('Delete'));
    $this->assertRaw($edit['label']);
    $this->assertText($edit['description']);
    $this->assertText($edit['mimetypes']);
    $this->assertText(t('Known MIME types'));

    // Modify file type.
    $edit['label'] = t('New type label');
    $this->drupalPostForm(NULL, array('label' => $edit['label']), t('Save'));
    $this->assertText(t('The file type @type has been updated.', array('@type' => $edit['label'])));
    $this->assertText($edit['label']);

    // Disable and re-enable file type.
    $this->drupalGet('admin/structure/file-types/manage/' . $edit['id'] . '/disable');
    $this->assertText(t('Are you sure you want to disable the file type @type?', array('@type' => $edit['label'])));
    $this->drupalPostForm(NULL, array(), t('Disable'));
    $this->assertText(t('The file type @type has been disabled.', array('@type' => $edit['label'])));
    $this->assertFieldByXPath("//tbody//tr[5]//td[1]", $edit['label']);
    $this->assertLink(t('Enable'));
    $this->assertLinkByHref('admin/structure/file-types/manage/' . $edit['id'] . '/enable');
    $this->drupalGet('admin/structure/file-types/manage/' . $edit['id'] . '/enable');
    $this->assertText(t('Are you sure you want to enable the file type @type?', array('@type' => $edit['label'])));
    $this->drupalPostForm(NULL, array(), t('Enable'));
    $this->assertText(t('The file type @type has been enabled.', array('@type' => $edit['label'])));
    $this->assertFieldByXPath("//tbody//tr[4]//td[1]", $edit['label']);

    // Delete newly created type.
    $this->drupalGet('admin/structure/file-types/manage/' . $edit['id'] . '/delete');
    $this->assertText(t('Are you sure you want to delete the file type @type?', array('@type' => $edit['label'])));
    $this->drupalPostForm(NULL, array(), t('Delete'));
    $this->assertText(t('The file type @type has been deleted.', array('@type' => $edit['label'])));
    $this->drupalGet('admin/structure/file-types');
    $this->assertNoText($edit['label']);

    // Edit pre-defined file type.
    $this->drupalGet('admin/structure/file-types/manage/image/edit');
    $this->assertRaw(t('Image'));
    $this->assertText("image/*");
    $this->drupalPostForm(NULL, array('label' => t('Funky images')), t('Save'));
    $this->assertText(t('The file type @type has been updated.', array('@type' => t('Funky images'))));
    $this->assertText(t('Funky image'));
  }
}
