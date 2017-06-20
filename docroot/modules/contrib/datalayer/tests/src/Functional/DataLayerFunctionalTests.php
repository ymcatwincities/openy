<?php

namespace Drupal\Tests\datalayer\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Functional test cases for datalayer module.
 *
 * @group DataLayer
 */
class DataLayerFunctionalTests extends BrowserTestBase {

  /**
   * Modules to install.
   *
   * @var array
   */
  public static $modules = ['node', 'datalayer'];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $admin_user = $this->drupalCreateUser([
      'access administration pages',
      'administer nodes',
      'administer site configuration',
    ]);
    $this->drupalLogin($admin_user);

  }

  /**
   * Test DataLayer variable output by name.
   *
   * This will be helpful when/if the variable name can be customized.
   *
   * @see https://www.drupal.org/node/2300577
   */
  public function testDataLayerVariableOutputByName() {
    $output = $this->drupalGet('node');
    $assert = $this->assertSession();
    $assert->pageTextContains('dataLayer = [{');
  }

  /**
   * Test DataLayer JS language settings.
   */
  public function testDataLayerJsLanguageSettings() {
    $output = $this->drupalGet('node');
    $assert = $this->assertSession();
    $assert->pageTextContains('"dataLayer":{"defaultLang"');
  }

}
