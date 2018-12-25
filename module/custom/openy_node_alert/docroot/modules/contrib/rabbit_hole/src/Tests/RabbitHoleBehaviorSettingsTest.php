<?php

namespace Drupal\rabbit_hole\Tests;

use Drupal\simpletest\WebTestBase;
use Drupal\node\Entity\Node;
use Drupal\rabbit_hole\Entity\BehaviorSettings;

/**
 * Test the RabbitHoleBehaviorSettings configuration entity functionality.
 *
 * @group rabbit_hole
 */
class RabbitHoleBehaviorSettingsTest extends WebTestBase {
  const DEFAULT_TEST_ENTITY = 'node';
  const DEFAULT_ACTION = 'bundle_default';
  const DEFAULT_OVERRIDE = BehaviorSettings::OVERRIDE_ALLOW;
  const DEFAULT_REDIRECT_CODE = BehaviorSettings::REDIRECT_NOT_APPLICABLE;
  const DEFAULT_BUNDLE_ACTION = 'display_page';
  const DEFAULT_BUNDLE_OVERRIDE = BehaviorSettings::OVERRIDE_ALLOW;
  const DEFAULT_BUNDLE_REDIRECT_CODE = BehaviorSettings::REDIRECT_NOT_APPLICABLE;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('rabbit_hole', self::DEFAULT_TEST_ENTITY);

  private $behaviorSettingsManager;

  private $configFactory;

  private $testNodeType;

  private $testNode;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->configFactory = $this->container->get('config.factory');
    $this->behaviorSettingsManager = $this->container
      ->get('rabbit_hole.behavior_settings_manager');
    $this->testNodeType = $this->generateTestNodeType();
    $this->testNode = $this->generateTestNode();
  }

  /**
   * Test that a BehaviorSettings can be found and contains correct values.
   *
   * Test that a saved BehaviorSettings entity can be found by the config system
   * and contains the correct values.
   */
  public function testSettings() {
    $this->saveAndTestExpectedValues(self::DEFAULT_ACTION,
      __METHOD__, '', 'test_behavior_settings');
  }

  /**
   * Test that the default bundle settings exist and have the expected values.
   */
  public function testBundleSettingsDefault() {
    $settings = \Drupal::config('rabbit_hole.behavior_settings.default');
    $this->assertEqual($settings->get('action'),
      self::DEFAULT_BUNDLE_ACTION,
      'Unexpected default action');
    $this->assertEqual($settings->get('allow_override'),
      self::DEFAULT_BUNDLE_OVERRIDE, 'Unexpected default override');
    $this->assertEqual($settings->get('redirect_code'),
      self::DEFAULT_BUNDLE_REDIRECT_CODE, 'Unexpected default redirect');
  }

  /**
   * Test that a BehaviourSettings can be given an ID and found later.
   *
   * Test that a saved BehaviourSettings entity can be given an ID based on
   * a generated bundle (a NodeType in this case) and be found based on that ID.
   */
  public function testBundleSettings() {
    $this->createTestNodeType();
    $this->saveAndTestExpectedValues('page_not_found', __METHOD__,
      self::DEFAULT_TEST_ENTITY, $this->testNodeType->id());
    $this->deleteTestNodeType();
  }

  /**
   * Test loading behavior settings for a nonexistent bundle returns defaults.
   */
  public function testLoadBundleSettingsWithDefault() {
    // We search for a bundle that doesn't exist (named from a UUID) expecting
    // to receive the default value.
    $action = $this->behaviorSettingsManager->loadBehaviorSettingsAsConfig(
      self::DEFAULT_TEST_ENTITY,
      'f4515736-cfa0-4e38-b3ed-1306f56bd2a1')->get('action');
    $this->assertEqual(self::DEFAULT_BUNDLE_ACTION, $action,
      'Unexpected default action');
  }

  /**
   * Test loading editable for nonexistent behavior settings returns NULL.
   */
  public function testLoadNullEditable() {
    $editable = $this->behaviorSettingsManager
      ->loadBehaviorSettingsAsEditableConfig(self::DEFAULT_TEST_ENTITY,
          '6b92ed36-f17f-4799-97d0-ae1801ed37ff');
    $this->assertEqual($editable, NULL);
  }

  /**
   * Helper function to test saving and confirming config.
   */
  private function saveAndTestExpectedValues($expected_action,
    $calling_method, $entity_type_label = '', $entity_id = NULL) {

    // Delete key if it already exists.
    $editable = $this->behaviorSettingsManager->loadBehaviorSettingsAsEditableConfig(
      $entity_type_label, $entity_id);
    if (isset($editable)) {
      $editable->delete();
    }

    $this->behaviorSettingsManager->saveBehaviorSettings(array(
      'action' => $expected_action,
      'allow_override' => 0,
      'redirect_code' => 0,
      'redirect' => '',
    ), $entity_type_label, $entity_id);
    $action = $this->behaviorSettingsManager->loadBehaviorSettingsAsConfig(
      $entity_type_label, $entity_id)->get('action');
    $this->assertEqual($action, $expected_action, 'Unexpected action '
      . ' (called from ' . $calling_method . ')');

    // Clean up the entity afterwards.
    $this->behaviorSettingsManager->loadBehaviorSettingsAsEditableConfig(
      $entity_type_label, $entity_id)->delete();
  }

  /**
   * Helper function to generate the test node type.
   */
  private function generateTestNodeType() {
    return \entity_create('node_type',
      array(
        'type' => 'test_behavior_settings_node_type',
        'name' => 'Test Behavior Settings Node Type',
      )
    );
  }

  /**
   * Helper function to generate the test node.
   */
  private function generateTestNode() {
    return Node::create(
      array(
        'nid' => NULL,
        'type' => $this->testNodeType->id(),
        'title' => 'Test Behavior Settings Node',
      )
    );
  }

  /**
   * Helper function to create the test node type in the database.
   */
  private function createTestNodeType() {
    $this->testNodeType->save();
  }

  /**
   * Helper function to delete the test node type from the database.
   */
  private function deleteTestNodeType() {
    $this->testNodeType->delete();
  }

  /**
   * Helper function to create the test node in the database.
   */
  private function createTestNode() {
    $this->testNode->save();
  }

  /**
   * Helper function to delete the test node from the database.
   */
  private function deleteTestNode() {
    $this->testNode->delete();
  }

}
