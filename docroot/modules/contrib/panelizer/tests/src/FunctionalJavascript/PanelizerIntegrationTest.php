<?php

namespace Drupal\Tests\panelizer\FunctionalJavascript;

use Drupal\simpletest\ContentTypeCreationTrait;
use Drupal\Tests\panels_ipe\FunctionalJavascript\PanelsIPETestBase;

/**
 * Tests the JavaScript functionality of Panels IPE with Panelizer.
 *
 * @group panelizer
 */
class PanelizerIntegrationTest extends PanelsIPETestBase {

  use ContentTypeCreationTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'field_ui',
    'node',
    'panels',
    'panels_ipe',
    'panelizer',
    'system',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Create a user with appropriate permissions to use Panels IPE.
    $admin_user = $this->drupalCreateUser([
      'access content',
      'access panels in-place editing',
      'administer blocks',
      'administer content types',
      'administer nodes',
      'administer node display',
      'administer panelizer',
    ]);
    $this->drupalLogin($admin_user);

    // Create the "Basic Page" content type.
    $this->createContentType([
      'type' => 'page',
      'name' => 'Basic Page',
    ]);

    // Enable Panelizer for the "Basic Page" content type.
    $this->drupalGet('admin/structure/types/manage/page/display');
    $this->submitForm(['panelizer[enable]' => 1], t('Save'));

    // Create a new Basic Page.
    $this->drupalGet('node/add/page');
    $this->submitForm(['title[0][value]' => 'Test Node'], t('Save'));

    $this->test_route = 'node/1';
  }

  /**
   * Tests that the IPE editing session is specific to a user.
   */
  public function testUserEditSession() {
    $this->visitIPERoute();
    $this->assertSession()->elementExists('css', '.layout--onecol');

    // Change the layout to lock the IPE.
    $this->changeLayout('Columns: 2', 'layout_twocol');
    $this->assertSession()->elementExists('css', '.layout--twocol');
    $this->assertSession()->elementNotExists('css', '.layout--onecol');

    // Create a second node.
    $this->drupalGet('node/add/page');
    $this->submitForm(['title[0][value]' => 'Test Node 2'], t('Save'));
    $this->test_route = 'node/2';

    // Ensure the second node does not use the session of the other node.
    $this->visitIPERoute();
    $this->assertSession()->elementExists('css', '.layout--onecol');
    $this->assertSession()->elementNotExists('css', '.layout--twocol');
  }

}
