<?php

namespace Drupal\optimizely\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Create users with no, some, and optimizely permissions.
 *
 * Use those users to test access to module related pages.
 *
 * @group Optimizely
 */
class OptimizelyAccessTest extends WebTestBase {

  protected $listingPage = 'admin/config/system/optimizely';
  protected $addUpdatePage = 'admin/config/system/optimizely/add_update';
  protected $deletePage = 'admin/config/system/optimizely/delete/2';
  protected $settingsPage = 'admin/config/system/optimizely/settings';
  protected $ajaxCallbackPage = 'ajax/optimizely';

  protected $noPermissionsUser;
  protected $somePermissionsUser;
  protected $privilegedUser;

  protected $optimizelyPermission = 'administer optimizely';

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['optimizely', 'node'];

  /**
   * {@inheritdoc}
   */
  public static function getInfo() {
    return [
      'name' => 'Optimizely Access',
      'description' => 'Test that no part of the Optimizely module administration' .
      ' interface can be accessed without the necessary permissions.',
      'group' => 'Optimizely',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function setUp() {

    parent::setUp();

    $this->drupalCreateContentType(['type' => 'page', 'name' => 'Basic page']);

    $this->noPermissionsUser = $this->drupalCreateUser([]);

    $this->somePermissionsUser = $this->drupalCreateUser([
      'access content',
      'create page content',
      'edit own page content',
    ]);

    // Create an admin user. The user will have the privilege
    // 'administer optimizely'. This privilege is needed to access all
    // administration functionality of the module.
    $this->privilegedUser = $this->drupalCreateUser([
      'access content',
      'create page content',
      'edit own page content',
      // 'administer url aliases',
      // 'create url aliases',.
      $this->optimizelyPermission,
    ]);

  }

  /**
   * Test that the Optimizely permission itself is valid.
   */
  public function testOptimizelyPermission() {

    $valid = $this->checkPermissions(['name' => $this->optimizelyPermission]);
    $this->assertTrue($valid, t("<strong> '@perm' is a valid permission.</strong>",
                                ['@perm' => $this->optimizelyPermission]),
                                'Optimizely');
  }

  /**
   * Test access to module functionality by users without permission.
   */
  public function testUserNoPermission() {

    $this->checkNoAccess($this->noPermissionsUser);
    $this->checkNoAccess($this->somePermissionsUser);

  }

  /**
   * Check that a user does not have access to the Optimizely pages.
   */
  private function checkNoAccess($user) {

    $access_forbidden = '403';

    $this->drupalLogin($user);

    $this->drupalGet($this->listingPage);
    $this->assertResponse($access_forbidden,
      "<strong>User without $this->optimizelyPermission permission may not" .
      " access project listing page -> $this->listingPage </strong>",
      'Optimizely');

    $this->drupalGet($this->addUpdatePage);
    $this->assertResponse($access_forbidden,
      "<strong>User without $this->optimizelyPermission permission may not" .
      " access project add/update page -> $this->addUpdatePage </strong>",
      'Optimizely');

    $this->drupalGet($this->deletePage);
    $this->assertResponse($access_forbidden,
      "<strong>User without $this->optimizelyPermission permission may not" .
      " access project delete page -> $this->deletePage </strong>",
      'Optimizely');

    $this->drupalGet($this->settingsPage);
    $this->assertResponse($access_forbidden,
      "<strong>User without $this->optimizelyPermission permission may not" .
      " access project settings page -> $this->settingsPage </strong>",
      'Optimizely');

    $this->drupalGet($this->ajaxCallbackPage);
    $this->assertResponse($access_forbidden,
      "<strong>User without $this->optimizelyPermission permission may not" .
      " access AJAX callback URL -> $this->ajaxCallbackPage </strong>",
      'Optimizely');

    $this->drupalLogout();

  }

  /**
   * Test access allowed to module functionality by user with permission.
   */
  public function testUserWithPermission() {

    $access_ok = '200';

    $this->drupalLogin($this->privilegedUser);

    $this->drupalGet($this->listingPage);
    $this->assertResponse($access_ok,
      "<strong>User with $this->optimizelyPermission permission may" .
      " access project listing page -> $this->listingPage </strong>",
      'Optimizely');

    $this->drupalGet($this->addUpdatePage);
    $this->assertResponse($access_ok,
      "<strong>User with $this->optimizelyPermission permission may" .
      " access project add/update page -> $this->addUpdatePage </strong>",
      'Optimizely');

    $this->drupalGet($this->deletePage);
    $this->assertResponse($access_ok,
      "<strong>User with $this->optimizelyPermission permission may" .
      " access project delete page -> $this->deletePage </strong>",
      'Optimizely');

    $this->drupalGet($this->settingsPage);
    $this->assertResponse($access_ok,
      "<strong>User with $this->optimizelyPermission permission may" .
      " access project settings page -> $this->settingsPage </strong>",
      'Optimizely');

    $this->drupalLogout();

  }

}
