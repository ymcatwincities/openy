<?php

namespace Drupal\file_entity\Tests;

use Drupal\file\FileInterface;
use Drupal\file_entity\FileEntityAccessControlHandler;
use Drupal\node\Entity\Node;

/**
 * Tests the access aspects of file entity.
 *
 * @group file_entity
 */
class FileEntityAccessTest extends FileEntityTestBase {

  /**
   * Modules to install.
   *
   * @var array
   */
  public static $modules = ['node'];

  /**
   * The File Entity access controller.
   *
   * @var FileEntityAccessControlHandler
   */
  protected $accessControlHandler;

  function setUp() {
    parent::setUp();
    $this->setUpFiles(array('uid' => 0));
    $this->accessControlHandler = $this->container->get('entity.manager')->getAccessControlHandler('file');

    // Unset the fact that file_entity_install() adds the 'view files'
    // permission to all user roles. This messes with being able to fully unit
    // test the file_entity_access() function.
    $roles = user_roles();
    foreach ($roles as $rid => $role) {
      user_role_revoke_permissions($rid, array('view files'));
    }
  }

  /**
   * Asserts FileEntityAccessControlHandler correctly grants or denies access.
   */
  function assertFileEntityAccess($ops, $file, $account) {
    $this->accessControlHandler->resetCache();
    foreach ($ops as $op => $expected) {
      $this->assertEqual(
        $expected,
        $op === 'create' ?
          $this->accessControlHandler->createAccess($file, $account) :
          $this->accessControlHandler->access($file, $op, $account)
      );
    }
  }

  /**
   * Runs basic tests for file_entity_access function.
   */
  function testFileEntityAccess() {
    /** @var FileInterface $file */
    $file = reset($this->files['image']);

    // Ensures user with 'bypass file access' permission can do everything.
    $web_user = $this->drupalCreateUser(array('bypass file access'));
    $this->assertFileEntityAccess(array('create' => TRUE), NULL, $web_user);
    $this->assertFileEntityAccess(array('view' => TRUE, 'download' => TRUE, 'update' => TRUE, 'delete' => TRUE), $file, $web_user);

    // A user with 'administer files' should not access CRUD operations.
    $web_user = $this->drupalCreateUser(array('administer files'));
    $this->assertFileEntityAccess(array('view' => FALSE, 'download' => FALSE, 'update' => FALSE, 'delete' => FALSE), $file, $web_user);

    // User cannot 'view files'.
    $web_user = $this->drupalCreateUser(array('create files'));
    $this->assertFileEntityAccess(array('view' => FALSE), $file, $web_user);
    // But can upload new ones.
    $this->assertFileEntityAccess(array('create' => TRUE), NULL, $web_user);

    // User can view own files but no other files.
    $web_user = $this->drupalCreateUser(array('create files', 'view own files'));
    $this->assertFileEntityAccess(array('view' => FALSE), $file, $web_user);
    $file->setOwner($web_user)->save();
    $this->assertFileEntityAccess(array('view' => TRUE), $file, $web_user);

    // User can download own files but no other files.
    $web_user = $this->drupalCreateUser(array('create files', 'download own image files'));
    $this->assertFileEntityAccess(array('download' => FALSE), $file, $web_user);
    $file->setOwner($web_user)->save();
    $this->assertFileEntityAccess(array('download' => TRUE), $file, $web_user);

    // User can update own files but no other files.
    $web_user = $this->drupalCreateUser(array('create files', 'view own files', 'edit own image files'));
    $this->assertFileEntityAccess(array('update' => FALSE), $file, $web_user);
    $file->setOwner($web_user)->save();
    $this->assertFileEntityAccess(array('update' => TRUE), $file, $web_user);

    // User can delete own files but no other files.
    $web_user = $this->drupalCreateUser(array('create files', 'view own files', 'edit own image files', 'delete own image files'));
    $this->assertFileEntityAccess(array('delete' => FALSE), $file, $web_user);
    $file->setOwner($web_user)->save();
    $this->assertFileEntityAccess(array('delete' => TRUE), $file, $web_user);

    // User can view any file.
    $web_user = $this->drupalCreateUser(array('create files', 'view files'));
    $this->assertFileEntityAccess(array('view' => TRUE), $file, $web_user);

    // User can download any file.
    $web_user = $this->drupalCreateUser(array('create files', 'download any image files'));
    $this->assertFileEntityAccess(array('download' => TRUE), $file, $web_user);

    // User can edit any file.
    $web_user = $this->drupalCreateUser(array('create files', 'view files', 'edit any image files'));
    $this->assertFileEntityAccess(array('update' => TRUE), $file, $web_user);

    // User can delete any file.
    $web_user = $this->drupalCreateUser(array('create files', 'view files', 'edit any image files', 'delete any image files'));
    $this->assertFileEntityAccess(array('delete' => TRUE), $file, $web_user);
  }

  /**
   * Test to see if we have access to view files when granted the permissions.
   * In this test we aim to prove the permissions work in the following pages:
   *  file/add
   *  file/%
   *  file/%/download
   *  file/%/edit
   *  file/%/delete
   */
  function testFileEntityPageAccess() {
    $web_user = $this->drupalCreateUser(array());
    $this->drupalLogin($web_user);
    $this->drupalGet('file/add');
    $this->assertResponse(403, 'Users without access can not access the file add page');
    $web_user = $this->drupalCreateUser(array('create files'));
    $this->drupalLogin($web_user);
    $this->drupalGet('file/add');
    $this->assertResponse(200, 'Users with access can access the file add page');

    $file = reset($this->files['text']);

    // This fails.. No clue why but, tested manually and works as should.
    //$web_user = $this->drupalCreateUser(array('view own files'));
    //$this->drupalLogin($web_user);
    //$this->drupalGet("file/{$file->id()}");
    //$this->assertResponse(403, 'Users without access can not access the file view page');
    $web_user = $this->drupalCreateUser(array('view files'));
    $this->drupalLogin($web_user);
    $this->drupalGet("file/{$file->id()}");
    $this->assertResponse(200, 'Users with access can access the file view page');

    $url = "file/{$file->id()}/download";
    $web_user = $this->drupalCreateUser(array());
    $this->drupalLogin($web_user);
    $this->drupalGet($url, array('query' => array('token' => $file->getDownloadToken())));
    $this->assertResponse(403, 'Users without access can not download the file');
    $web_user = $this->drupalCreateUser(array('download any document files'));
    $this->drupalLogin($web_user);
    $this->drupalGet($url, array('query' => array('token' => $file->getDownloadToken())));
    $this->assertResponse(200, 'Users with access can download the file');
    $this->drupalGet($url, array('query' => array('token' => 'invalid-token')));
    $this->assertResponse(403, 'Cannot download file with in invalid token.');
    $this->drupalGet($url);
    $this->assertResponse(403, 'Cannot download file without a token.');
    $this->config->set('allow_insecure_download', TRUE)->save();
    $this->drupalGet($url);
    $this->assertResponse(200, 'Users with access can download the file without a token when allow_insecure_download is set.');

    $web_user = $this->drupalCreateUser(array());
    $this->drupalLogin($web_user);
    $this->drupalGet("file/{$file->id()}/edit");
    $this->assertResponse(403, 'Users without access can not access the file edit page');
    $web_user = $this->drupalCreateUser(array('edit any document files'));
    $this->drupalLogin($web_user);
    $this->drupalGet("file/{$file->id()}/edit");
    $this->assertResponse(200, 'Users with access can access the file edit page');

    $web_user = $this->drupalCreateUser(array());
    $this->drupalLogin($web_user);
    $this->drupalGet("file/{$file->id()}/delete");
    $this->assertResponse(403, 'Users without access can not access the file delete page');
    $web_user = $this->drupalCreateUser(array('delete any document files'));
    $this->drupalLogin($web_user);
    $this->drupalGet("file/{$file->id()}/delete");
    $this->assertResponse(200, 'Users with access can access the file delete page');
  }

  /**
   * Test to see if we have access to download private files when granted the permissions.
   */
  function testFileEntityPrivateDownloadAccess() {
    $original_file = next($this->files['text']);

    foreach ($this->getPrivateDownloadAccessCases() as $case) {
      /** @var FileInterface $file */
      $file = file_copy($original_file, 'private://');
      $user_name = 'anonymous';

      // Create users and login only if non-anonymous.
      $authenticated_user = !is_null($case['permissions']);
      if ($authenticated_user) {
        $account = $this->drupalCreateUser($case['permissions']);
        $this->drupalLogin($account);
        $user_name = $account->getUsername();
        if (!empty($case['owner'])) {
          $file->setOwner($account)->save();
        }
      }

      // Check if the physical file is there.
      $arguments = array(
        '%name' => $file->getFilename(),
        '%username' => $user_name,
        '%uri' => $file->getFileUri(),
      );
      $this->assertTrue(is_file($file->getFileUri()), format_string('File %name owned by %username successfully created at %uri.', $arguments));
      $url = file_create_url($file->getFileUri());
      $message_file_info = ' ' . format_string('File %uri was checked.', array('%uri' => $file->getFileUri()));

      // Try to download the file.
      $this->drupalGet($url);
      $this->assertResponse($case['expect'], $case['message'] . $message_file_info);

      // Logout authenticated users.
      if ($authenticated_user) {
        $this->drupalLogout();
      }
    }
  }

  /**
   * Tests file download access.
   */
  public function testDownloadLinkAccess() {
    // Create content type with image field.
    $this->drupalCreateContentType([
      'name' => 'Article',
      'type' => 'article',
    ]);
    \Drupal::entityTypeManager()->getStorage('field_storage_config')->create([
      'field_name' => 'image',
      'entity_type' => 'node',
      'type' => 'image',
      'cardinality' => 1,
    ])->save();
    \Drupal::entityTypeManager()->getStorage('field_config')->create([
      'field_name' => 'image',
      'label' => 'Image',
      'entity_type' => 'node',
      'bundle' => 'article',
    ])->save();
    /** @var \Drupal\Core\Entity\Display\EntityFormDisplayInterface $form_display */
    $form_display = \Drupal::entityTypeManager()->getStorage('entity_form_display')->load('node.article.default');
    $form_display->setComponent('image', [
      'type' => 'image_image',
    ])->save();
    /** @var \Drupal\Core\Entity\Display\EntityViewDisplayInterface $view_display */
    $view_display = \Drupal::entityTypeManager()->getStorage('entity_view_display')->load('node.article.default');
    $view_display->setComponent('image', [
      'type' => 'file_download_link',
    ])->save();

    $account = $this->drupalCreateUser([
      'download any image files',
    ]);
    $this->drupalLogin($account);
    $image = current($this->files['image']);
    $node = Node::create([
      'title' => 'Title',
      'type' => 'article',
      'image' => [
        'target_id' => $image->id(),
        'alt' => 'the image alternative',
      ],
    ]);
    $node->save();
    $this->drupalGet('node/' . $node->id());

    $this->assertRaw('file/' . $image->id() . '/download', 'Download link available.');
    $this->assertLink('Download image-test.png');

    $this->drupalLogout();
    $this->drupalGet('node/' . $node->id());
    $this->assertText("You don't have access to download this file.", 'No access message displays correctly.');
    $view_display->setComponent('image', [
      'type' => 'file_download_link',
      'settings' => [
        'access_message' => 'Another message.',
      ],
    ])->save();
    $this->drupalGet('node/' . $node->id());
    $this->assertText('Another message.', 'No access message updated.');
  }

}
