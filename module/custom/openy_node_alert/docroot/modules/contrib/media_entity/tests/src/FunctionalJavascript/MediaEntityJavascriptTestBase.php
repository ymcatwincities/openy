<?php

namespace Drupal\Tests\media_entity\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\JavascriptTestBase;
use Drupal\Tests\media_entity\Functional\MediaEntityFunctionalTestTrait;

/**
 * Base class for Media Entity Javascript functional tests.
 *
 * @package Drupal\Tests\media_entity\FunctionalJavascript
 */
abstract class MediaEntityJavascriptTestBase extends JavascriptTestBase {

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

  /**
   * Waits and asserts that a given element is visible.
   *
   * @param string $selector
   *   The CSS selector.
   * @param int $timeout
   *   (Optional) Timeout in milliseconds, defaults to 1000.
   * @param string $message
   *   (Optional) Message to pass to assertJsCondition().
   */
  protected function waitUntilVisible($selector, $timeout = 1000, $message = '') {
    $condition = "jQuery('" . $selector . ":visible').length > 0";
    $this->assertJsCondition($condition, $timeout, $message);
  }

}
