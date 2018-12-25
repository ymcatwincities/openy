<?php

namespace Drupal\Tests\restui\Functional;

use Drupal\FunctionalJavascriptTests\JavascriptTestBase;
use Drupal\rest\RestResourceConfigInterface;

/**
 * Tests Rest UI functionality.
 *
 * @group restui
 */
class RestUITest extends JavascriptTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['node', 'restui'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Create a user with permissions to manage.
    $permissions = [
      'administer site configuration',
      'administer rest resources'
    ];
    $account = $this->drupalCreateUser($permissions);

    // Initiate user session.
    $this->drupalLogin($account);
  }

  /**
   * Tests enabling a resource and accessing it.
   */
  public function testConsumers() {
    // Check that user can access the administration interface.
    $this->drupalGet('admin/config/services/rest');
    $this->assertEquals(200, $this->getSession()->getStatusCode());

    // Get configuration page for Node resource.
    $this->drupalGet('admin/config/services/rest/resource/entity%3Anode/edit');
    $page = $this->getSession()->getPage();

    // Assert that the 'resource' configuration form is build as default.
    $this->assertSession()->fieldExists('wrapper[settings][methods][GET]');
    $this->assertSession()->fieldExists('wrapper[settings][formats][json]');
    $this->assertSession()->fieldExists('wrapper[settings][authentication][cookie]');

    // Method granularity.
    // Adjust the node resource so it allows GET method with JSON format and
    // Cookie authentication.
    $page->findField('granularity')->selectOption(RestResourceConfigInterface::METHOD_GRANULARITY);
    $this->assertSession()->waitForField('wrapper[methods][GET]');
    $page->findField('wrapper[methods][GET][GET]')->check();
    $page->findField('wrapper[methods][GET][settings][formats][json]')->check();
    $page->findField('wrapper[methods][GET][settings][auth][cookie]')->check();

    $page->pressButton('Save configuration');
    $this->assertSession()->pageTextContains('The resource has been updated.');

    // Resource granularity.
    // Adjust the node resource so it allows GET method, JSON format and
    // Cookie authentication.
    $this->drupalGet('admin/config/services/rest/resource/entity%3Anode/edit');

    $page = $this->getSession()->getPage();
    $page->findField('granularity')->selectOption(RestResourceConfigInterface::RESOURCE_GRANULARITY);
    $this->assertSession()->waitForField('wrapper[settings][methods][GET]');
    $page->findField('wrapper[settings][methods][GET]')->check();
    $page->findField('wrapper[settings][formats][json]')->check();
    $page->findField('wrapper[settings][authentication][cookie]')->check();

    $page->pressButton('Save configuration');
    $this->assertSession()->pageTextContains('The resource has been updated.');
  }

}
