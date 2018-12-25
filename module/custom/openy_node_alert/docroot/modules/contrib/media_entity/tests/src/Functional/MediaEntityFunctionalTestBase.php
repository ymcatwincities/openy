<?php

namespace Drupal\Tests\media_entity\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Base class for Media Entity functional tests.
 *
 * @package Drupal\Tests\media_entity\Functional
 */
abstract class MediaEntityFunctionalTestBase extends BrowserTestBase {

  use MediaEntityFunctionalTestTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'system',
    'node',
    'field_ui',
    'views_ui',
    'entity',
    'media_entity',
  ];

  /**
   * Permissions for the admin user that will be logged-in for test.
   *
   * @var array
   */
  protected static $adminUserPermissions = [
    // Media entity permissions.
    'administer media',
    'administer media fields',
    'administer media form display',
    'administer media display',
    'administer media bundles',
    'view media',
    'create media',
    'update media',
    'update any media',
    'delete media',
    'delete any media',
    'access media overview',
    // Other permissions.
    'administer views',
    'access content overview',
    'view all revisions',
    'administer content types',
    'administer node fields',
    'administer node form display',
    'bypass node access',
  ];

  /**
   * An admin test user account.
   *
   * @var \Drupal\Core\Session\AccountInterface;
   */
  protected $adminUser;

  /**
   * A non-admin test user account.
   *
   * @var \Drupal\Core\Session\AccountInterface;
   */
  protected $nonAdminUser;

  /**
   * The storage service.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface;
   */
  protected $storage;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Have two users ready to be used in tests.
    $this->adminUser = $this->drupalCreateUser(static::$adminUserPermissions);
    $this->nonAdminUser = $this->drupalCreateUser([]);
    // Start off logged in as admin.
    $this->drupalLogin($this->adminUser);

    $this->storage = $this->container->get('entity_type.manager')->getStorage('media');
  }

}
