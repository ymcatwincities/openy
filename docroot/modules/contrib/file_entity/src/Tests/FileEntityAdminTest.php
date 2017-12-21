<?php

namespace Drupal\file_entity\Tests;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\file_entity\Entity\FileEntity;
use Drupal\node\Entity\Node;
use Drupal\user\Entity\User;
use Drupal\views\Entity\View;

/**
 * Test file administration page functionality.
 *
 * @group file_entity
 */
class FileEntityAdminTest extends FileEntityTestBase {

  /** @var User */
  protected $userAdmin;

  /** @var User */
  protected $userBasic;

  /** @var User */
  protected $userViewOwn;

  /** @var User */
  protected $userViewPrivate;

  /** @var User */
  protected $userEditDelete;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['block'];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    // Add the tasks and actions blocks.
    $this->drupalPlaceBlock('local_actions_block');
    $this->drupalPlaceBlock('local_tasks_block');

    // Remove the "view files" permission which is set
    // by default for all users so we can test this permission
    // correctly.
    $roles = user_roles();
    foreach ($roles as $rid => $role) {
      user_role_revoke_permissions($rid, array('view files'));
    }

    $this->userAdmin = $this->drupalCreateUser(array('administer files', 'bypass file access'));
    $this->userBasic = $this->drupalCreateUser(array('administer files'));
    $this->userViewOwn = $this->drupalCreateUser(array('administer files', 'view own private files'));
    $this->userViewPrivate = $this->drupalCreateUser(array('administer files', 'view private files'));
    $this->userEditDelete = $this->drupalCreateUser(array(
      'administer files',
      'edit any document files',
      'delete any document files',
      'edit any image files',
      'delete any image files',
    ));

    // Enable the enhanced Files view.
    View::load('files')->disable()->save();
    View::load('file_entity_files')->enable()->save();
  }

  /**
   * Tests that the table sorting works on the files admin pages.
   */
  public function testFilesAdminSort() {
    $this->drupalLogin($this->userAdmin);
    $i = 0;
    foreach (array('dd', 'aa', 'DD', 'bb', 'cc', 'CC', 'AA', 'BB') as $prefix) {
      $this->createFileEntity(array('filename' => $prefix . $this->randomMachineName(6), 'created' => $i * 90000));
      $i++;
    }

    // Test that the default sort by file_managed.created DESC fires properly.
    $files_query = array();
    foreach (\Drupal::entityQuery('file')->sort('created', 'DESC')->execute() as $fid) {
      $files_query[] = FileEntity::load($fid)->label();
    }

    $this->drupalGet('admin/content/files');
    $list = $this->xpath('//table[@class="views-table views-view-table cols-10 responsive-enabled"]/tbody//tr');
    $entries = [];
    foreach ($list as $entry) {
      $entries[] = trim((string) $entry->td[1]->a);
    }
    $this->assertEqual($files_query, $entries, 'Files are sorted in the view according to the default query.');

    // Compare the rendered HTML node list to a query for the files ordered by
    // filename to account for possible database-dependent sort order.
    $files_query = array();
    foreach (\Drupal::entityQuery('file')->sort('filename')->execute() as $fid) {
      $files_query[] = FileEntity::load($fid)->label();
    }

    $this->drupalGet('admin/content/files', array('query' => array('sort' => 'asc', 'order' => 'filename')));
    $list = $this->xpath('//table[@class="views-table views-view-table cols-10 responsive-enabled"]/tbody//tr');
    $entries = [];
    foreach ($list as $entry) {
      $entries[] = trim((string) $entry->td[1]->a);
    }
    $this->assertEqual($files_query, $entries, 'Files are sorted in the view the same as they are in the query.');
  }

  /**
   * Tests files overview with different user permissions.
   */
  public function testFilesAdminPages() {
    $this->drupalLogin($this->userAdmin);

    /** @var FileEntity[] $files */
    $files['public_image'] = $this->createFileEntity(array(
      'scheme' => 'public',
      'uid' => $this->userBasic->id(),
      'type' => 'image',
    ));
    $files['public_document'] = $this->createFileEntity(array(
      'scheme' => 'public',
      'uid' => $this->userViewOwn->id(),
      'type' => 'document',
    ));
    $files['private_image'] = $this->createFileEntity(array(
      'scheme' => 'private',
      'uid' => $this->userBasic->id(),
      'type' => 'image',
    ));
    $files['private_document'] = $this->createFileEntity(array(
      'scheme' => 'private',
      'uid' => $this->userViewOwn->id(),
      'type' => 'document',
    ));

    // Verify view, edit, and delete links for any file.
    $this->drupalGet('admin/content/files');
    $this->assertResponse(200);
    $i = 0;
    foreach ($files as $file) {
      $this->assertLinkByHref('file/' . $file->id());
      $this->assertLinkByHref('file/' . $file->id() . '/edit');
      $this->assertLinkByHref('file/' . $file->id() . '/delete');
      // Verify tableselect.
      $this->assertFieldByName("bulk_form[$i]", NULL, t('Bulk form checkbox found.'));
    }

    // Verify no operation links are displayed for regular users.
    $this->drupalLogout();
    $this->drupalLogin($this->userBasic);
    $this->drupalGet('admin/content/files');
    $this->assertResponse(200);
    $this->assertLinkByHref('file/' . $files['public_image']->id());
    $this->assertLinkByHref('file/' . $files['public_document']->id());
    $this->assertNoLinkByHref('file/' . $files['public_document']->id() . '/download');
    $this->assertNoLinkByHref('file/' . $files['public_document']->id() . '/download');
    $this->assertNoLinkByHref('file/' . $files['public_image']->id() . '/edit');
    $this->assertNoLinkByHref('file/' . $files['public_image']->id() . '/delete');
    $this->assertNoLinkByHref('file/' . $files['public_document']->id() . '/edit');
    $this->assertNoLinkByHref('file/' . $files['public_document']->id() . '/delete');

    // Verify no tableselect.
    $this->assertNoFieldByName('bulk_form[' . $files['public_image']->id() . ']', '', t('No bulk form checkbox found.'));

    // Verify private file is displayed with permission.
    $this->drupalLogout();
    $this->drupalLogin($this->userViewOwn);
    $this->drupalGet('admin/content/files');
    $this->assertResponse(200);
    $this->assertLinkByHref($files['private_document']->url());
    // Verify no operation links are displayed.
    $this->drupalGet($files['private_document']->url('edit-form'));
    $this->assertResponse(403, 'User doesn\'t have permission to edit files');
    $this->drupalGet($files['private_document']->url('delete-form'));
    $this->assertResponse(403, 'User doesn\'t have permission to delete files');

    // Verify user cannot see private file of other users.
    $this->assertNoLinkByHref($files['private_image']->url());
    $this->assertNoLinkByHref($files['private_image']->url('edit-form'));
    $this->assertNoLinkByHref($files['private_image']->url('delete-form'));
    $this->assertNoLinkByHref($files['private_image']->downloadUrl()->toString());

    // Verify no tableselect.
    $this->assertNoFieldByName('bulk_form[' . $files['private_document']->id() . ']', '', t('No bulk form checkbox found.'));

    // Verify private file is displayed with permission.
    $this->drupalLogout();
    $this->drupalLogin($this->userViewPrivate);
    $this->drupalGet('admin/content/files');
    $this->assertResponse(200);

    // Verify user can see private file of other users.
    $this->assertLinkByHref('file/' . $files['private_document']->id());
    $this->assertLinkByHref('file/' . $files['private_image']->id());

    // Verify operation links are displayed for users with appropriate
    // permission.
    $this->drupalLogout();
    $this->drupalLogin($this->userEditDelete);
    $this->drupalGet('admin/content/files');
    $this->assertResponse(200);
    foreach ($files as $file) {
      $this->assertLinkByHref('file/' . $file->id());
      $this->assertLinkByHref('file/' . $file->id() . '/edit');
      $this->assertLinkByHref('file/' . $file->id() . '/delete');
      $this->assertLinkByHref('file/' . $file->id() . '/delete');
    }

    // Verify file access can be bypassed.
    $this->drupalLogout();
    $this->drupalLogin($this->userAdmin);
    $this->drupalGet('admin/content/files');
    $this->assertResponse(200);
    foreach ($files as $file) {
      $this->assertLinkByHref('file/' . $file->id());
      $this->assertLinkByHref('file/' . $file->id() . '/edit');
      $this->assertLinkByHref('file/' . $file->id() . '/delete');
      $this->assertLinkByHref('file/' . $file->id() . '/download');
    }
  }

  /**
   * Tests single and bulk operations on the file overview.
   */
  public function testFileOverviewOperations() {
    $this->setUpFiles();
    $this->drupalLogin($this->userEditDelete);

    // Test single operations.
    $this->drupalGet('admin/content/files');
    $this->assertLinkByHref('file/1/delete');
    $this->assertLinkByHref('file/2/delete');
    $this->drupalGet('file/1/delete');
    $this->assertTitle(t('Are you sure you want to delete the file @filename? | Drupal', array('@filename' => FileEntity::load(1)->label())));
    $this->drupalPostForm(NULL, array(), t('Delete'));
    $this->assertNoLinkByHref('file/1/delete');
    $this->assertLinkByHref('file/2/delete');

    // Test bulk status change.
    // The "first" file now has id 2, but bulk form fields start counting at 0.
    $this->assertTrue(FileEntity::load(2)->isPermanent());
    $this->assertTrue(FileEntity::load(3)->isPermanent());
    $this->assertTrue(FileEntity::load(4)->isPermanent());
    $this->assertTrue(FileEntity::load(5)->isPermanent());

    $this->drupalGet('admin/content/files', array('query' => array('order' => 'fid')));
    $edit = array(
      'action' => 'file_temporary_action',
      'bulk_form[0]' => 1,
      'bulk_form[1]' => 1,
      'bulk_form[2]' => 1,
    );
    $this->drupalPostForm(NULL, $edit, t('Apply to selected items'));

    \Drupal::entityManager()->getStorage('file')->resetCache();
    $this->assertFalse(FileEntity::load(2)->isPermanent());
    $this->assertFalse(FileEntity::load(3)->isPermanent());
    $this->assertFalse(FileEntity::load(4)->isPermanent());
    $this->assertTrue(FileEntity::load(5)->isPermanent());

    $this->drupalGet('admin/content/files', array('query' => array('order' => 'fid')));
    $edit = array(
      'action' => 'file_permanent_action',
      'bulk_form[0]' => 1,
      'bulk_form[1]' => 1,
    );
    $this->drupalPostForm(NULL, $edit, t('Apply to selected items'));

    \Drupal::entityManager()->getStorage('file')->resetCache();
    $this->assertTrue(FileEntity::load(2)->isPermanent());
    $this->assertTrue(FileEntity::load(3)->isPermanent());
    $this->assertFalse(FileEntity::load(4)->isPermanent());
    $this->assertTrue(FileEntity::load(5)->isPermanent());

    // Test bulk delete.
    $this->drupalGet('admin/content/files', array('query' => array('order' => 'fid')));
    $edit = array(
      'action' => 'file_delete_action',
      'bulk_form[0]' => 1,
      'bulk_form[1]' => 1,
    );
    $this->drupalPostForm(NULL, $edit, t('Apply to selected items'));
    $this->assertTitle(t('Are you sure you want to delete these files? | Drupal'));
    $this->assertLink('Cancel');
    $this->drupalPostForm(NULL, array(), t('Delete'));

    \Drupal::entityManager()->getStorage('file')->resetCache();
    $this->assertNull(FileEntity::load(2), 'File 2 is deleted.');
    $this->assertNull(FileEntity::load(3), 'File 3 is deleted.');
    $this->assertNotNull(FileEntity::load(4), 'File 4 is not deleted.');
  }

  /**
   * Tests the file usage view.
   */
  public function testUsageView() {
    $this->container->get('module_installer')->install(array('node'));
    \Drupal::service('router.builder')->rebuild();
    $file = $this->createFileEntity(array('uid' => $this->userAdmin));
    // @todo Next line causes an exception, core issue https://www.drupal.org/node/2462283
    $this->drupalLogin($this->userAdmin);

    // Check the usage links on the file overview.
    $this->drupalGet('admin/content/files');
    $this->assertLink('0 places');
    $this->assertNoLink('1 place');

    // Check the usage view.
    $this->clickLink('0 places');
    $this->assertText('This file is not currently used.');

    // Attach a file field to article nodes.
    $content_type = $this->drupalCreateContentType();
    $field_storage = FieldStorageConfig::create(array(
      'field_name' => 'used_file',
      'entity_type' => 'node',
      'type' => 'file',
    ));
    $field_storage->save();
    $field_instance = FieldConfig::create(array(
      'field_storage' => $field_storage,
      'entity_type' => 'node',
      'bundle' => $content_type->id(),
    ));
    $field_instance->save();

    // Create a node using a file.
    $node = Node::create(array(
      'title' => 'An article that uses a file',
      'type' => $content_type->id(),
      'used_file' => array(
        'target_id' => $file->id(),
        'display' => 1,
        'description' => '',
      ),
    ));
    $node->save();
    \Drupal::entityManager()->getStorage('node')->resetCache();
    \Drupal::entityManager()->getStorage('file')->resetCache();

    // Check that the usage link is updated.
    $this->drupalGet('admin/content/files');
    $this->assertLink('1 place');

    // Check that the using node shows up on the usage view.
    $this->clickLink('1 place');
    $this->assertLink('An article that uses a file');

    // Check local tasks.
    $this->clickLink('View');
    $this->assertResponse(200);
    $this->clickLink('Usage');
    $this->assertResponse(200);
  }
}
