<?php

namespace Drupal\rabbit_hole\Tests;

use Drupal\system\Tests\Plugin\PluginTestBase;

/**
 * Test the functionality of the RabbitHoleBehavior plugin.
 *
 * @group rabbit_hole
 */
class RabbitHoleBehaviorPluginTest extends PluginTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('rabbit_hole');

  /**
   * The plugin manager.
   *
   * @var \Drupal\Component\Plugin\PluginManagerInterface
   */
  private $manager;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->manager = $this->container->get('plugin.manager.rabbit_hole_behavior_plugin');
  }

  /**
   * Test the plugin manager.
   */
  public function testPluginManager() {
    // Check that we can get a behavior plugin.
    $this->assertNotNull($this->manager, 'Drupal plugin service returned a rabbit hole behavior service.');

    // Check that the behavior plugin manager is the type we expect.
    $this->assertEqual(get_class($this->manager), 'Drupal\rabbit_hole\Plugin\RabbitHoleBehaviorPluginManager');

    // Check the rabbit_hole module defines the expected number of behaviors.
    $behaviors = $this->manager->getDefinitions();
    $this->assertEqual(count($behaviors), 4, 'There are 4 behaviors.');

    // Check that the plugins defined by the rabbit_hole module are in the list
    // of plugins.
    $this->assertTrue($this->manager->hasDefinition('access_denied'), 'There is an access denied plugin');
    $this->assertTrue(isset($behaviors['access_denied']['label']), 'The access denied plugin has a label');
    $this->assertTrue($this->manager->hasDefinition('display_page'), 'There is a display the page plugin');
    $this->assertTrue(isset($behaviors['display_page']['label']), 'The display the page plugin has a label');
    $this->assertTrue($this->manager->hasDefinition('page_not_found'), 'There is a page not found plugin');
    $this->assertTrue(isset($behaviors['page_not_found']['label']), 'The page not found plugin has a label');
    $this->assertTrue($this->manager->hasDefinition('page_redirect'), 'There is a page redirect plugin');
    $this->assertTrue(isset($behaviors['page_redirect']['label']), 'The page redirect plugin has a label');
  }

  /**
   * Test the access denied plugin.
   */
  public function testAccessDeniedPlugin() {
    // Check we can create an instance of the plugin.
    $plugin = $this->manager->createInstance('access_denied', ['of' => 'configuration values']);
    $this->assertEqual(get_class($plugin), 'Drupal\rabbit_hole\Plugin\RabbitHoleBehaviorPlugin\AccessDenied', 'The access denied plugin is the correct type.');

    // Test the settings form.
    $form = $form_state = [];
    $plugin->settingsForm($form, $form_state, 'test');
    $this->assertEqual($form, [], 'Access denied plugin has no settings form.');
    $this->assertEqual($form_state, [], 'Access denied plugin settings form state was not changed.');

    // Check that the plugin performs the expected action.
    // TODO: Check that $plugin->performAction() throws a
    // \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException.
  }

  /**
   * Test the display page plugin.
   */
  public function testDisplayPagePlugin() {
    // Check we can create an instance of the plugin.
    $plugin = $this->manager->createInstance('display_page', ['of' => 'configuration values']);
    $this->assertEqual(get_class($plugin), 'Drupal\rabbit_hole\Plugin\RabbitHoleBehaviorPlugin\DisplayPage', 'The display page plugin is the correct type.');

    // Test the settings form.
    $form = $form_state = [];
    $plugin->settingsForm($form, $form_state, 'test');
    $this->assertEqual($form, [], 'Display page plugin has no settings form.');
    $this->assertEqual($form_state, [], 'Display page plugin settings form state was not changed.');

    // Check that the plugin performs the expected action.
    // TODO: Check that $plugin->performAction() throws nothing and returns
    // nothing.
  }

  /**
   * Test the page not found plugin.
   */
  public function testPageNotFoundPlugin() {
    // Check we can create an instance of the plugin.
    $plugin = $this->manager->createInstance('page_not_found', ['of' => 'configuration values']);
    $this->assertEqual(get_class($plugin), 'Drupal\rabbit_hole\Plugin\RabbitHoleBehaviorPlugin\PageNotFound', 'The page not found plugin is the correct type.');

    // Test the settings form.
    $form = $form_state = [];
    $plugin->settingsForm($form, $form_state, 'test');
    $this->assertEqual($form, [], 'Page not found plugin has no settings form.');
    $this->assertEqual($form_state, [], 'Page not found plugin settings form state was not changed.');

    // Check that the plugin performs the expected action.
    // TODO: Check that $plugin->performAction() throws a
    // \Symfony\Component\HttpKernel\Exception\NotFoundHttpException.
  }

  /**
   * Test the page redirect plugin.
   */
  public function testPageRedirectPlugin() {
    // Check we can create an instance of the plugin.
    $plugin = $this->manager->createInstance('page_redirect', ['of' => 'configuration values']);
    $this->assertEqual(get_class($plugin), 'Drupal\rabbit_hole\Plugin\RabbitHoleBehaviorPlugin\PageRedirect', 'The page redirect plugin is the correct type.');

    // Test the settings form.
    $form = $form_state = [];
    $plugin->settingsForm($form, $form_state, 'test');
    $this->assertNotEqual($form, [], 'Page redirect plugin defines a settings form.');
    $this->assertEqual($form_state, [], 'Page redirect plugin form state was not changed.');

    // Check that the plugin performs the expected action.
    // TODO: Check that $plugin->performAction() does what it's supposed to,
    // whatever that is.
  }

}
