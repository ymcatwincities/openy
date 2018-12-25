<?php

namespace Drupal\rh_node\Tests;

use Drupal\simpletest\WebTestBase;
use Drupal\node\Entity\NodeType;
use Drupal\node\Entity\Node;
use Drupal\rabbit_hole\Entity\BehaviorSettings;

/**
 * Test the functionality of the rabbit hole form additions to the node form.
 *
 * @group rh_node
 */
class BehaviorSettingsFormAlterationsTest extends WebTestBase {
  const TEST_CONTENT_TYPE_ID = 'rh_node_test_content_type';
  const CONTENT_TYPE_PATH_PREFIX = 'admin/structure/types/manage/';
  const CONTENT_ADD_PREFIX = 'node/add/';
  const TEST_NODE_NAME = 'rh_node_test_node';
  const DEFAULT_BUNDLE_ACTION = 'display_page';
  const DEFAULT_ACTION = 'bundle_default';

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
    // TODO: These tests should be expanded for users with different types of
    // permissions.
    $this->user = $this->drupalCreateUser(array(
      'bypass node access', 'administer content types',
      'rabbit hole administer node',
    ));

    $this->behaviorSettingsManager = $this->container
      ->get('rabbit_hole.behavior_settings_manager');
  }

  /**
   * Test that bundle form of new content type contains rabbit hole settings.
   *
   * Test that the content type form of a newly created content type contains
   * the expected fields.
   */
  public function testDefaultContentTypeForm() {
    $this->createTestContentType();
    $this->loadContentTypeFormForTestType();

    $this->assertFieldByName('rh_override', BehaviorSettings::OVERRIDE_ALLOW);
    $this->assertFieldByName('rh_action', 'access_denied');
    $this->assertFieldByName('rh_action', 'display_page');
    $this->assertFieldByName('rh_action', 'page_not_found');
    $this->assertFieldByName('rh_action', 'page_redirect');
    $default_option_id = 'edit-rh-action-'
      . str_replace('_', '-', self::DEFAULT_BUNDLE_ACTION);
    $this->assertFieldChecked($default_option_id);
  }

  /**
   * Test that saving bundle changes creates a settings config key for bundle.
   *
   * Test that saving changes to a content type form creates an appropriate
   * behavior settings config key.
   */
  public function testContentTypeFormFirstSave() {
    $test_content_type_id = $this->createTestContentType();
    $this->loadContentTypeFormForTestType();

    $override = BehaviorSettings::OVERRIDE_DISALLOW;
    $action = 'access_denied';

    $this->drupalPostForm(NULL, array(
      'rh_override' => $override,
      'rh_action' => $action,
    ), t('Save content type'));

    $saved_config = $this->behaviorSettingsManager->loadBehaviorSettingsAsConfig(
      'node_type', $test_content_type_id, TRUE);
    $this->assertEqual($saved_config->get('action'), $action);
    $this->assertEqual($saved_config->get('allow_override'), $override);
  }

  /**
   * Test that bundle form with a configured bundle behaviour loads config.
   *
   * Test that a content type form of a content type with a configured behavior
   * properly loads configuration.
   */
  public function testContentTypeFormExistingBehavior() {
    $action = 'page_not_found';
    $override = BehaviorSettings::OVERRIDE_DISALLOW;

    $test_content_type_id = $this->createTestContentType();
    $this->behaviorSettingsManager->saveBehaviorSettings(array(
      'action' => $action,
      'allow_override' => $override,
      'redirect_code' => BehaviorSettings::REDIRECT_NOT_APPLICABLE,
    ), 'node_type', $test_content_type_id
    );

    $this->loadContentTypeFormForTestType();

    $this->assertFieldByName('rh_override', $override);
    $default_option_id = 'edit-rh-action-'
      . str_replace('_', '-', $action);
    $this->assertFieldChecked($default_option_id);
  }

  /**
   * Test new changes to bundle with existing rabbit hole settings changes key.
   *
   * Test that saving changes to a content type form which already has
   * configured rabbit hole behavior settings changes the existing key.
   */
  public function testContentTypeFormSave() {
    $test_content_type_id = $this->createTestContentType();

    $this->behaviorSettingsManager->saveBehaviorSettings(array(
      'action' => 'access_denied',
      'allow_override' => BehaviorSettings::OVERRIDE_DISALLOW,
      'redirect_code' => BehaviorSettings::REDIRECT_NOT_APPLICABLE,
    ), 'node_type', $test_content_type_id
    );

    $this->loadContentTypeFormForTestType();

    $action = 'page_not_found';
    $override = BehaviorSettings::OVERRIDE_ALLOW;

    $this->drupalPostForm(NULL, array(
      'rh_override' => $override,
      'rh_action' => $action,
    ), t('Save content type'));

    $saved_config = $this->behaviorSettingsManager->loadBehaviorSettingsAsConfig(
      'node_type', $test_content_type_id, TRUE);

    $this->assertEqual($saved_config->get('action'), $action);
    $this->assertEqual($saved_config->get('allow_override'), $override);
  }

  /**
   * Test that we can save settings for node that did not previously have them.
   *
   * Test that an existing node that previously didn't have settings will have
   * settings saved when the node form is saved.
   */
  public function testExistingNodeNoConfigSave() {
    $this->createTestContentType();
    $node_id = $this->createTestNode();

    $action = 'access_denied';

    $this->loadNodeFormForTestNode($node_id);
    $this->drupalPostForm(NULL, array(
      'rh_action' => $action,
    ), t('Save'));

    $node = Node::Load($node_id);
    $this->assertEqual($node->rh_action->value, $action);
  }

  /**
   * Test that an existing node entity is edited on saving the node form.
   */
  public function testExistingNodeSave() {
    $this->createTestContentType();
    $node_id = $this->createTestNode('display_page');

    $action = 'access_denied';

    $this->loadNodeFormForTestNode($node_id);
    $this->drupalPostForm(NULL, array(
      'rh_action' => $action,
    ), t('Save'));

    $node = Node::Load($node_id);
    $this->assertEqual($node->rh_action->value, $action);
  }

  /**
   * Test that when a node form is loaded it defaults the bundle configuration.
   */
  public function testDefaultNodeSettingsLoad() {
    $this->createTestContentType();
    $this->loadNewNodeFormForTestContentType();

    $this->assertNoFieldByName('rh_override');
    $this->assertFieldByName('rh_action', 'access_denied');
    $this->assertFieldByName('rh_action', 'display_page');
    $this->assertFieldByName('rh_action', 'page_not_found');
    $this->assertFieldByName('rh_action', 'page_redirect');
    $default_option_id = 'edit-rh-action-'
      . str_replace('_', '-', self::DEFAULT_ACTION);
    $this->assertFieldChecked($default_option_id);
  }

  /**
   * Test that a node form correctly loads previously saved behavior settings.
   */
  public function testExistingNodeSettingsLoad() {
    $this->createTestContentType();

    $action = 'access_denied';
    $node_id = $this->createTestNode($action);
    $this->loadNodeFormForTestNode($node_id);
    $default_option_id = 'edit-rh-action-'
      . str_replace('_', '-', $action);
    $this->assertFieldChecked($default_option_id);
  }

  /**
   * Create a content type for testing.
   *
   * @return string
   *   The content type ID.
   */
  private function createTestContentType() {
    $node_type = NodeType::create(
      array(
        'type' => self::TEST_CONTENT_TYPE_ID,
        'name' => self::TEST_CONTENT_TYPE_ID,
      )
    );
    $node_type->save();
    return $node_type->id();
  }

  /**
   * Create a node for testing.
   *
   * @return int
   *   The node ID.
   */
  private function createTestNode($action = '') {
    $node = Node::create(
      array(
        'nid' => NULL,
        'type' => self::TEST_CONTENT_TYPE_ID,
        'title' => 'Test Behavior Settings Node',
      )
    );
    if (isset($action)) {
      $node->set('rh_action', $action);
    }
    $node->save();
    return $node->id();
  }

  /**
   * Load the test content type form.
   */
  private function loadContentTypeFormForTestType() {
    $this->drupalLogin($this->user);
    $this->drupalGet(self::CONTENT_TYPE_PATH_PREFIX
      . self::TEST_CONTENT_TYPE_ID);
    $this->assertResponse(200);
  }

  /**
   * Load the add new node form for the test content type.
   */
  private function loadNewNodeFormForTestContentType() {
    $this->drupalLogin($this->user);
    $this->drupalGet(self::CONTENT_ADD_PREFIX
      . self::TEST_CONTENT_TYPE_ID);
    $this->assertResponse(200);
  }

  /**
   * Load the node form for the test node with the given ID.
   *
   * @param string $test_node_id
   *   The ID of the test node.
   */
  private function loadNodeFormForTestNode($test_node_id) {
    $this->drupalLogin($this->user);
    $this->drupalGet('node/' . $test_node_id . '/edit');
    $this->assertResponse(200);
  }

}
