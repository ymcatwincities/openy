<?php

/**
 * @file
 * Contains \Drupal\page_manager_ui\Tests\AddVariantSelectionTest.
 */

namespace Drupal\page_manager_ui\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Tests selection criteria for page variants.
 *
 * @group page_manager
 */
class AddVariantSelectionTest  extends WebTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['page_manager', 'page_manager_ui', 'node'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->drupalCreateContentType(['type' => 'article', 'name' => 'Article']);
    $this->drupalCreateContentType(['type' => 'page', 'name' => 'Page']);
    $this->drupalLogin($this->drupalCreateUser(['administer pages', 'create article content']));

    $this->drupalPlaceBlock('page_title_block');
  }

  /**
   * Tests configuration of the selection criteria wizard step.
   */
  public function testSelectionCriteria() {
    // Create a node, and check its page.
    $node = $this->drupalCreateNode(['type' => 'article']);
    $node2 = $this->drupalCreateNode(['type' => 'article']);
    $this->drupalGet('node/' . $node->id());
    $this->assertResponse(200);
    $this->assertText($node->label());
    $this->assertTitle($node->label() . ' | Drupal');

    // Create a new page entity.
    $edit_page = [
      'label' => 'Selection criteria',
      'id' => 'selection_criteria',
      'path' => 'selection-criteria',
      'variant_plugin_id' => 'block_display'
    ];
    $this->drupalPostForm('admin/structure/page_manager/add', $edit_page, 'Next');
    $this->drupalPostForm(NULL, [], 'Next');
    $this->drupalPostForm(NULL, [], 'Finish');
    $this->clickLink('Add variant');
    $edit = [
      'label' => 'Variant two',
      'variant_plugin_id' => 'block_display',
      'wizard_options[contexts]' => TRUE,
      'wizard_options[selection]' => TRUE,
    ];
    $this->drupalPostForm(NULL, $edit, 'Next');
    // Add a static context for each node to the page variant.
    $contexts = array(
      array(
        'title' => 'Static Node',
        'machine_name' => 'static_node',
        'description' => 'Static node 1',
        'node' => $node,
      ),
      array(
        'title' => 'Static Node 2',
        'machine_name' => 'static_node_2',
        'description' => 'Static node 2',
        'node' => $node2,
      ),
    );
    foreach ($contexts as $context) {
      $edit = [
        'context' => 'entity:node',
      ];
      $this->drupalPostForm(NULL, $edit, 'Add new context');
      $edit = [
        'label' => $context['title'],
        'machine_name' => $context['machine_name'],
        'description' => $context['description'],
        'context_value' => $context['node']->getTitle() . ' (' . $context['node']->id() . ')',
      ];
      $this->drupalPostForm(NULL, $edit, 'Save');
      $this->assertText($context['title']);
    }
    $this->drupalPostForm(NULL, [], 'Next');

    // Configure selection criteria.
    $edit = [
      'conditions' => 'entity_bundle:node',
    ];
    $this->drupalPostForm(NULL, $edit, 'Add Condition');

    $edit = [
      'bundles[article]' => TRUE,
      'bundles[page]' => TRUE,
      'context_mapping[node]' => 'static_node_2',
    ];
    $this->drupalPostForm(NULL, $edit, 'Save');
    $this->assertText('Content type is article or page');
    $this->clickLink('Edit');
    $edit = [
      'bundles[article]' => TRUE,
      'context_mapping[node]' => 'static_node_2',
    ];
    $this->drupalPostForm(NULL, $edit, 'Save');
    $this->assertText('Content type is article');
    $this->clickLink('Delete');
    $this->drupalPostForm(NULL, [], 'Delete');
    $this->assertNoText('Content type is article');
    $this->drupalPostForm(NULL, [], 'Next');

    // Configure the new variant.
    $variant_edit = [
      'variant_settings[page_title]' => 'Variant two criteria test',
    ];
    $this->drupalPostForm(NULL, $variant_edit, 'Next');

    // Add a block that renders the node from the first static context.
    $this->clickLink('Add new block');
    $this->clickLink('Entity view (Content)');
    $edit = [
      'settings[label]' => 'Static node view',
      'settings[label_display]' => 1,
      'settings[view_mode]' => 'default',
      'region' => 'top',
    ];
    $this->drupalPostForm(NULL, $edit, 'Add block');
    $this->assertText($edit['settings[label]']);

    // Add a block that renders the node from the second static context.
    $this->clickLink('Add new block');
    $this->clickLink('Entity view (Content)');
    $edit = [
      'settings[label]' => 'Static node 2 view',
      'settings[label_display]' => 1,
      'settings[view_mode]' => 'default',
      'region' => 'bottom',
      'context_mapping[entity]' => $contexts[1]['machine_name'],
    ];
    $this->drupalPostForm(NULL, $edit, 'Add block');
    $this->assertText($edit['settings[label]']);
    $this->drupalPostForm(NULL, [], 'Finish');
  }

}
