<?php

/**
 * @file
 * Contains \Drupal\page_manager_ui\Tests\PageParametersTest.php.
 */

namespace Drupal\page_manager_ui\Tests;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Url;
use Drupal\page_manager\Entity\Page;
use Drupal\simpletest\WebTestBase;

/**
 * Tests the page parameters capabilities.
 *
 * @group page_manager_ui
 */
class PageParametersTest extends WebTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['block', 'page_manager_ui', 'node'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->drupalCreateContentType(['type' => 'article', 'name' => 'Article']);

    $this->drupalPlaceBlock('local_tasks_block');
    $this->drupalPlaceBlock('local_actions_block');
    $this->drupalPlaceBlock('system_branding_block');
    $this->drupalPlaceBlock('page_title_block');

    $this->drupalLogin($this->drupalCreateUser([
      'administer pages',
      'access administration pages',
      'view the administration theme',
      'create article content',
    ]));
  }

  /**
   * Tests page parameters when adding a page and when editing it.
   */
  public function testParameters() {
    $node = $this->drupalCreateNode(['type' => 'article']);

    // Create a page.
    $this->drupalGet('admin/structure');
    $this->clickLink('Pages');
    $this->clickLink('Add page');
    $edit = [
      'id' => 'foo',
      'label' => 'Foo',
      'path' => 'admin/foo/{node}',
      'variant_plugin_id' => 'block_display',
      'use_admin_theme' => TRUE,
      'description' => 'Sample test page.',
    ];
    $this->drupalPostForm(NULL, $edit, 'Next');

    // Test the 'Parameters' step.
    $this->assertTitle('Page parameters | Drupal');
    $access_path = 'admin/structure/page_manager/add/foo/parameters';
    $this->assertUrl($access_path . '?js=nojs');
    $this->assertNoText('There are no parameters defined for this page.');

    // Edit the node parameter.
    $this->clickLink('Edit');
    $this->assertTitle('Edit  parameter | Drupal');
    $edit = [
      'type' => 'entity:node',
    ];
    $this->drupalPostForm(NULL, $edit, 'Update parameter');
    $this->assertText('The node parameter has been updated.');

    // Skip the variant General configuration step.
    $this->drupalPostForm(NULL, [], 'Next');

    // Add the Node block to the top region.
    $this->drupalPostForm(NULL, [], 'Next');
    $this->clickLink('Add new block');
    $this->clickLink('Entity view (Content)');
    $edit = [
      'region' => 'top',
    ];
    $this->drupalPostForm(NULL, $edit, 'Add block');

    // Finish the wizard.
    $this->drupalPostForm(NULL, [], 'Finish');
    $this->assertRaw(new FormattableMarkup('Saved the %label Page.', ['%label' => 'Foo']));

    // Check that the node's title is visible at the page.
    $this->drupalGet('admin/foo/' . $node->id());
    $this->assertResponse(200);
    $this->assertText($node->getTitle());
  }

}
