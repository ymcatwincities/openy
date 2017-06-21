<?php

namespace Drupal\Tests\panelizer\Functional;

use Drupal\Component\Serialization\Json;
use Drupal\Tests\BrowserTestBase;

/**
 * Confirm that the IPE functionality works.
 *
 * @group panelizer
 */
class PanelizerIpeTest extends BrowserTestBase {

  use PanelizerTestTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    // Modules for core functionality.
    'node',
    'field',
    'field_ui',
    'user',

    // Core dependencies.
    'layout_discovery',

    // Contrib dependencies.
    'ctools',
    'panels',
    'panels_ipe',

    // This module.
    'panelizer',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Reload all caches.
    $this->rebuildAll();
  }

  /**
   * The content type that will be tested against.
   *
   * @string
   */
  protected $content_type = 'page';

  /**
   * Create a user with the required permissions.
   *
   * @param array $perms
   *   Any additiona permissions that need to be added.
   *
   * @return Drupal\user\Entity\User
   *   The user account that was created.
   */
  protected function createAdminUser(array $perms = array()) {
    $perms += [
      // From system.
      'access administration pages',

      // Content permissions.
      'access content',
      'administer content types',
      'administer nodes',
      'create page content',
      'edit any page content',
      'edit own page content',

      // From Field UI.
      'administer node display',

      // From Panels.
      'access panels in-place editing',
    ];
    $this->verbose('<pre>' . print_r($perms, TRUE) . '</pre>');
    return $this->drupalCreateUser($perms);
  }

  /**
   * Test that the IPE functionality as user 1, which should cover all options.
   */
  public function testAdminUser() {
    $this->setupContentType($this->content_type);

    // Create a test node.
    $node = $this->createTestNode();

    // Log in as user 1.
    $this->loginUser1();

    // Load the test node.
    $this->drupalGet('node/' . $node->id());
    $this->assertResponse(200);

    // Confirm the JSON Drupal settings are appropriate.
    $drupalSettings = NULL;
    $matches = [];
    if (preg_match('@<script type="application/json" data-drupal-selector="drupal-settings-json">([^<]*)</script>@', $this->getRawContent(), $matches)) {
      $drupalSettings = Json::decode($matches[1]);
      $this->verbose('<pre>' . print_r($drupalSettings, TRUE) . '</pre>');
    }
    $this->assertNotNull($drupalSettings);
    if (!empty($drupalSettings)) {
      $this->assertTrue(isset($drupalSettings['panels_ipe']));
      $this->assertTrue(isset($drupalSettings['panels_ipe']['regions']));
      $this->assertTrue(isset($drupalSettings['panels_ipe']['layout']));
      $this->assertTrue(isset($drupalSettings['panels_ipe']['user_permission']));
      $this->assertTrue(isset($drupalSettings['panels_ipe']['panels_display']));
      $this->assertTrue(isset($drupalSettings['panels_ipe']['unsaved']));
      $this->assertTrue(isset($drupalSettings['panelizer']));
      $this->assertTrue(isset($drupalSettings['panelizer']['entity']));
      $this->assertTrue(isset($drupalSettings['panelizer']['entity']['entity_type_id']));
      $this->assertEqual($drupalSettings['panelizer']['entity']['entity_type_id'], 'node');
      $this->assertTrue(isset($drupalSettings['panelizer']['entity']['entity_id']));
      $this->assertEqual($drupalSettings['panelizer']['entity']['entity_id'], $node->id());
      $this->assertTrue(isset($drupalSettings['panelizer']['user_permission']));
      $this->assertTrue(isset($drupalSettings['panelizer']['user_permission']['revert']));
      $this->assertTrue(isset($drupalSettings['panelizer']['user_permission']['save_default']));
    }
  }

  /**
   * Confirm the 'administer panelizer' permission works.
   */
  public function testAdministerPanelizerPermission() {
    $this->setupContentType($this->content_type);

    // Create a test node.
    $node = $this->createTestNode();

    // Create a new user with the permissions being tested.
    $perms = [
      'administer panelizer',
    ];
    $account = $this->createAdminUser($perms);
    $this->drupalLogin($account);

    // Load the test node.
    $this->drupalGet('node/' . $node->id());
    $this->assertResponse(200);

    // Confirm the appropriate DOM structures are present for the IPE.
    $drupalSettings = NULL;
    $matches = [];
    if (preg_match('@<script type="application/json" data-drupal-selector="drupal-settings-json">([^<]*)</script>@', $this->getRawContent(), $matches)) {
      $drupalSettings = Json::decode($matches[1]);
      $this->verbose('<pre>' . print_r($drupalSettings, TRUE) . '</pre>');
    }
    $this->assertNotNull($drupalSettings);
    if (!empty($drupalSettings)) {
      $this->assertTrue(isset($drupalSettings['panels_ipe']));
      $this->assertTrue(isset($drupalSettings['panels_ipe']['regions']));
      $this->assertTrue(isset($drupalSettings['panels_ipe']['layout']));
      $this->assertTrue(isset($drupalSettings['panels_ipe']['user_permission']));
      $this->assertTrue(isset($drupalSettings['panels_ipe']['panels_display']));
      $this->assertTrue(isset($drupalSettings['panels_ipe']['unsaved']));
      $this->assertTrue(isset($drupalSettings['panelizer']));
      $this->assertTrue(isset($drupalSettings['panelizer']['entity']));
      $this->assertTrue(isset($drupalSettings['panelizer']['entity']['entity_type_id']));
      $this->assertEqual($drupalSettings['panelizer']['entity']['entity_type_id'], 'node');
      $this->assertTrue(isset($drupalSettings['panelizer']['entity']['entity_id']));
      $this->assertEqual($drupalSettings['panelizer']['entity']['entity_id'], $node->id());
      $this->assertTrue(isset($drupalSettings['panelizer']['user_permission']));
      $this->assertTrue(isset($drupalSettings['panelizer']['user_permission']['revert']));
      $this->assertTrue(isset($drupalSettings['panelizer']['user_permission']['save_default']));
      $this->assertTrue($drupalSettings['panelizer']['user_permission']['revert']);
      $this->assertTrue($drupalSettings['panelizer']['user_permission']['save_default']);
    }
  }

  /**
   * @todo Confirm the 'set panelizer default' permission works.
   */
  // public function testSetDefault() {
  // }

  /**
   * @todo Confirm the 'administer panelizer $entity_type_id $bundle defaults'
   * permission works.
   */
  // public function testAdministerEntityDefaults() {
  // }

  /**
   * @todo Confirm the 'administer panelizer $entity_type_id $bundle content'
   * permission works.
   */
  public function testAdministerEntityContentPermission() {
    $this->setupContentType($this->content_type);

    // Need the node for the tests below, so create it now.
    $node = $this->createTestNode();

    $perms = [
      'administer panelizer node page content',
    ];
    $drupalSettings = $this->setupPermissionTests($perms, $node);
    $this->assertNotNull($drupalSettings);

    // @todo How to tell if the user can change the display or add new items vs
    // other tasks?
    if (!empty($drupalSettings)) {
      $this->assertTrue(isset($drupalSettings['panels_ipe']));
      $this->assertTrue(isset($drupalSettings['panels_ipe']['regions']));
      $this->assertTrue(isset($drupalSettings['panels_ipe']['layout']));
      $this->assertTrue(isset($drupalSettings['panels_ipe']['user_permission']));
      $this->assertTrue(isset($drupalSettings['panels_ipe']['panels_display']));
      $this->assertTrue(isset($drupalSettings['panels_ipe']['unsaved']));
      $this->assertTrue(isset($drupalSettings['panelizer']));
      $this->assertTrue(isset($drupalSettings['panelizer']['entity']));
      $this->assertTrue(isset($drupalSettings['panelizer']['entity']['entity_type_id']));
      $this->assertEqual($drupalSettings['panelizer']['entity']['entity_type_id'], 'node');
      $this->assertTrue(isset($drupalSettings['panelizer']['entity']['entity_id']));
      $this->assertEqual($drupalSettings['panelizer']['entity']['entity_id'], $node->id());
      $this->assertTrue(isset($drupalSettings['panelizer']['user_permission']));
      $this->assertTrue(isset($drupalSettings['panelizer']['user_permission']['revert']));
      $this->assertTrue(isset($drupalSettings['panelizer']['user_permission']['save_default']));
    }
  }

  /**
   * @todo Confirm the 'administer panelizer $entity_type_id $bundle layout'
   * permission works.
   */
  public function testAdministerEntityLayoutPermission() {
    $this->setupContentType($this->content_type);

    // Need the node for the tests below, so create it now.
    $node = $this->createTestNode();

    // Test with just the 'layout' permission
    $perms = [
      'administer panelizer node page layout',
    ];
    $drupalSettings = $this->setupPermissionTests($perms, $node);
    $this->assertNotNull($drupalSettings);

    if (!empty($drupalSettings)) {
      $this->assertFalse(isset($drupalSettings['panels_ipe']));
      $this->assertFalse(isset($drupalSettings['panelizer']));
    }

    // Make sure the user is logged out before doing another pass.
    $this->drupalLogout();

    // Test with the 'revert' and the 'content' permission.
    $perms = [
      // The permission to be tested.
      'administer panelizer node page layout',
      // This permission has to be enabled for the 'revert' permission to work.
      'administer panelizer node page content',
    ];
    $drupalSettings = $this->setupPermissionTests($perms, $node);
    $this->assertNotNull($drupalSettings);

    // @todo How to tell if the user can change the layout vs other tasks?
    if (!empty($drupalSettings)) {
      $this->assertTrue(isset($drupalSettings['panels_ipe']));
      $this->assertTrue(isset($drupalSettings['panels_ipe']['regions']));
      $this->assertTrue(isset($drupalSettings['panels_ipe']['layout']));
      $this->assertTrue(isset($drupalSettings['panels_ipe']['user_permission']));
      $this->assertTrue(isset($drupalSettings['panels_ipe']['panels_display']));
      $this->assertTrue(isset($drupalSettings['panels_ipe']['unsaved']));
      $this->assertTrue(isset($drupalSettings['panelizer']));
      $this->assertTrue(isset($drupalSettings['panelizer']['entity']));
      $this->assertTrue(isset($drupalSettings['panelizer']['entity']['entity_type_id']));
      $this->assertEqual($drupalSettings['panelizer']['entity']['entity_type_id'], 'node');
      $this->assertTrue(isset($drupalSettings['panelizer']['entity']['entity_id']));
      $this->assertEqual($drupalSettings['panelizer']['entity']['entity_id'], $node->id());
      $this->assertTrue(isset($drupalSettings['panelizer']['user_permission']));
      $this->assertTrue(isset($drupalSettings['panelizer']['user_permission']['revert']));
      $this->assertTrue(isset($drupalSettings['panelizer']['user_permission']['save_default']));
      $this->assertFalse($drupalSettings['panelizer']['user_permission']['revert']);
      $this->assertFalse($drupalSettings['panelizer']['user_permission']['save_default']);
    }
  }

  /**
   * @todo Confirm the 'administer panelizer $entity_type_id $bundle revert'
   * permission works.
   */
  public function testAdministerEntityRevertPermission() {
    $this->setupContentType($this->content_type);

    // Need the node for the tests below, so create it now.
    $node = $this->createTestNode();

    // Test with just the 'revert' permission
    $perms = [
      'administer panelizer node page revert',
    ];
    $drupalSettings = $this->setupPermissionTests($perms, $node);
    $this->assertNotNull($drupalSettings);

    if (!empty($drupalSettings)) {
      $this->assertFalse(isset($drupalSettings['panels_ipe']));
      $this->assertFalse(isset($drupalSettings['panelizer']));
    }

    // Make sure the user is logged out before doing another pass.
    $this->drupalLogout();

    // Test with the 'revert' and the 'content' permission.
    $perms = [
      // The permission to be tested.
      'administer panelizer node page revert',
      // This permission has to be enabled for the 'revert' permission to work.
      'administer panelizer node page content',
    ];
    $drupalSettings = $this->setupPermissionTests($perms, $node);
    $this->assertNotNull($drupalSettings);

    if (!empty($drupalSettings)) {
      $this->assertTrue(isset($drupalSettings['panels_ipe']));
      $this->assertTrue(isset($drupalSettings['panels_ipe']['regions']));
      $this->assertTrue(isset($drupalSettings['panels_ipe']['layout']));
      $this->assertTrue(isset($drupalSettings['panels_ipe']['user_permission']));
      $this->assertTrue(isset($drupalSettings['panels_ipe']['panels_display']));
      $this->assertTrue(isset($drupalSettings['panels_ipe']['unsaved']));
      $this->assertTrue(isset($drupalSettings['panelizer']));
      $this->assertTrue(isset($drupalSettings['panelizer']['entity']));
      $this->assertTrue(isset($drupalSettings['panelizer']['entity']['entity_type_id']));
      $this->assertEqual($drupalSettings['panelizer']['entity']['entity_type_id'], 'node');
      $this->assertTrue(isset($drupalSettings['panelizer']['entity']['entity_id']));
      $this->assertEqual($drupalSettings['panelizer']['entity']['entity_id'], $node->id());
      $this->assertTrue(isset($drupalSettings['panelizer']['user_permission']));
      $this->assertTrue(isset($drupalSettings['panelizer']['user_permission']['revert']));
      $this->assertTrue(isset($drupalSettings['panelizer']['user_permission']['save_default']));
      $this->assertTrue($drupalSettings['panelizer']['user_permission']['revert']);
      $this->assertFalse($drupalSettings['panelizer']['user_permission']['save_default']);
    }
  }

  /**
   * Do the necessary setup work for the individual permissions tests.
   *
   * @param array $perms
   *   Any additiona permissions that need to be added.
   * @param obj $node
   *   The node to test against, if none provided one will be generated.
   *
   * @return array
   *   The full drupalSettings JSON structure in array format.
   */
  protected function setupPermissionTests(array $perms, $node = NULL) {
    // Create a new user with the permissions being tested.
    $account = $this->createAdminUser($perms);
    $this->drupalLogin($account);

    // Make sure there's a test node to work with.
    if (empty($node)) {
      $node = $this->createTestNode();
    }

    // Load the test node.
    $this->drupalGet('node/' . $node->id());
    $this->assertResponse(200);

    // Extract the drupalSettings structure and return it.
    $drupalSettings = NULL;
    $matches = [];
    if (preg_match('@<script type="application/json" data-drupal-selector="drupal-settings-json">([^<]*)</script>@', $this->getRawContent(), $matches)) {
      $drupalSettings = Json::decode($matches[1]);
      $this->verbose('<pre>' . print_r($drupalSettings, TRUE) . '</pre>');
    }
    return $drupalSettings;
  }

}
