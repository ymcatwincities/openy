<?php

/**
 * @file
 */

namespace Drupal\Tests\panels_ipe\FunctionalJavascript;

use Drupal\simpletest\ContentTypeCreationTrait;

/**
 * Tests the JavaScript functionality of Panels IPE with Panelizer.
 *
 * @group panels
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
    $this->submitForm(['title[0][value]' => 'Test Node'], t('Save and publish'));

    $this->test_route = 'node/1';
  }

}
