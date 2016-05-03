<?php

/**
 * @file
 * Definition of Drupal\entity_clone\Tests\EntityCloneFileTest.
 */

namespace Drupal\entity_clone\Tests;

use Drupal\file\Entity\File;
use Drupal\simpletest\WebTestBase;

/**
 * Create a filer and test a clone.
 *
 * @group entity_clone
 */
class EntityCloneFileTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['entity_clone', 'file'];

  /**
   * Permissions to grant admin user.
   *
   * @var array
   */
  protected $permissions = [
    'clone file entity'
  ];

  /**
   * An administrative user with permission to configure files settings.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * Sets the test up.
   */
  protected function setUp() {
    parent::setUp();

    $this->adminUser = $this->drupalCreateUser($this->permissions);
    $this->drupalLogin($this->adminUser);
  }

  public function testFileEntityClone() {
    /** @var \Drupal\file\FileInterface $file */
    $file = File::create(array(
      'uid' => 1,
      'filename' => 'druplicon.txt',
      'uri' => 'public://druplicon.txt',
      'filemime' => 'text/plain',
      'status' => FILE_STATUS_PERMANENT,
    ));
    file_put_contents($file->getFileUri(), 'hello world');
    $file->save();

    $this->drupalPostForm('entity_clone/file/' . $file->id(), [], t('Clone'));

    $files = \Drupal::entityTypeManager()
      ->getStorage('file')
      ->loadByProperties([
        'filename' => 'druplicon.txt - Cloned',
      ]);
    $file = reset($files);
    $this->assertTrue($file, 'Test file cloned found in database.');

    $this->assertEqual($file->getFileUri(), 'public://druplicon_0.txt', 'The stored file is also cloned.');
  }

}

