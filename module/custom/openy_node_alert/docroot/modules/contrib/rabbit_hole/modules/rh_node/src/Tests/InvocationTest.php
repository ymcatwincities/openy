<?php

namespace Drupal\rh_node\Tests;

use Drupal\simpletest\WebTestBase;
use Drupal\node\Entity\NodeType;
use Drupal\node\Entity\Node;

/**
 * Test that rabbit hole behaviors are invoked correctly for nodes.
 *
 * @group rh_node
 */
class InvocationTest extends WebTestBase {
  const TEST_CONTENT_TYPE_ID = 'rh_node_test_content_type';
  const NODE_BASE_PATH = '/node/';

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('rabbit_hole', 'rh_node', 'node');

  private $user;

  private $behaviorSettingsManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->behaviorSettingsManager = $this->container
      ->get('rabbit_hole.behavior_settings_manager');
  }

  /**
   * Test that a fresh node with a fresh content type takes the default action.
   */
  public function testNodeDefaults() {
    $type = $this->createTestNodeType();
    $node = $this->createTestNodeOfType($type->id());
    $this->drupalGet(self::NODE_BASE_PATH . $node->id());
    $this->assertResponse(200);
  }

  /**
   * Test action not set or set to bundle_default will default to bundle action.
   */
  public function testDefaultToBundle() {
    $type = $this->createTestNodeType('access_denied');
    $node = $this->createTestNodeOfType($type->id());
    $this->drupalGet(self::NODE_BASE_PATH . $node->id());
    $this->assertResponse(403);

    $node2 = $this->createTestNodeOfType($type->id(), 'bundle_default');
    $this->drupalGet(self::NODE_BASE_PATH . $node2->id());
    $this->assertResponse(403);
  }

  /**
   * Test that a node set to access_denied returns a 403 response.
   */
  public function testAccessDenied() {
    $type = $this->createTestNodeType();
    $node = $this->createTestNodeOfType($type->id(), 'access_denied');
    $this->drupalGet(self::NODE_BASE_PATH . $node->id());
    $this->assertResponse(403);
  }

  /**
   * Test that a node set to display_page returns a 200 response.
   */
  public function testDisplayPage() {
    $type = $this->createTestNodeType('access_denied');
    $node = $this->createTestNodeOfType($type->id(), 'display_page');
    $this->drupalGet(self::NODE_BASE_PATH . $node->id());
    $this->assertResponse(200);
  }

  /**
   * TODO.
   */
  public function testUrlRedirects() {
    $type = $this->createTestNodeType('access_denied');

    $this->testUrlRedirect(301, $type);
    $this->testUrlRedirect(302, $type);
    $this->testUrlRedirect(303, $type);
    // $this->testUrlRedirect(304, $type);.
    $this->testUrlRedirect(305, $type);
    $this->testUrlRedirect(307, $type);
  }

  /**
   * Test URL redirects with tokens.
   *
   * @todo
   */
  public function testTokenizedUrlRedirect() {}

  /**
   * Test redirects that use PHP code.
   *
   * @todo
   */
  public function testCodeRedirect() {}

  /**
   * Test that a node set to page_not_found overrides returns a 404.
   */
  public function testPageNotFound() {
    $type = $this->createTestNodeType();
    $node = $this->createTestNodeOfType($type->id(), 'page_not_found');
    $this->drupalGet(self::NODE_BASE_PATH . $node->id());
    $this->assertResponse(404);
  }

  /**
   * TODO.
   */
  private function createTestNodeType($action = NULL) {
    $node_type = NodeType::create(
      array(
        'type' => self::TEST_CONTENT_TYPE_ID,
        'name' => self::TEST_CONTENT_TYPE_ID,
      )
    );
    $node_type->save();
    if (isset($action)) {
      $this->behaviorSettingsManager->saveBehaviorSettings(
        array('action' => $action, 'allow_override' => TRUE), 'node_type', $node_type->id());
    }
    return $node_type;
  }

  /**
   * TODO.
   */
  private function createTestNodeOfType($node_type_id = self::TEST_CONTENT_TYPE_ID, $action = NULL) {
    $node = Node::create(
      array(
        'nid' => NULL,
        'type' => $node_type_id,
        'title' => 'Test Behavior Settings Node',
      )
    );
    if (isset($action)) {
      $node->set('rh_action', $action);
    }
    $node->save();
    return $node;
  }

  /**
   * Test some simple URL redirects.
   */
  private function testUrlRedirect($redirect_code, $type) {
    global $base_root;

    $target_node = $this->createTestNodeOfType($type->id(), 'display_page');
    $destination_path = self::NODE_BASE_PATH . $target_node->id();

    $node = $this->createTestNodeOfType($type->id(), 'page_redirect');
    $node->set('rh_redirect', $base_root . $destination_path);
    $node->set('rh_redirect_response', $redirect_code);
    $node->save();
    $this->drupalGet(self::NODE_BASE_PATH . $node->id());
    $this->assertUrl($base_root . $destination_path);
  }

}
