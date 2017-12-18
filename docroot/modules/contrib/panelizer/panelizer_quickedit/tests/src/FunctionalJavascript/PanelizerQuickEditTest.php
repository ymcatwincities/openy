<?php

namespace Drupal\Tests\panelizer_quickedit\FunctionalJavascript;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\FunctionalJavascriptTests\JavascriptTestBase;

/**
 * Tests that a Panelized Node can be Quick-Edited.
 *
 * @group panelizer
 */
class PanelizerQuickEditTest extends JavascriptTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['contextual', 'quickedit', 'field_ui', 'node', 'panelizer_quickedit'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->drupalCreateContentType(['type' => 'page', 'name' => 'Page']);

    // Add a plain text field for this content type.
    FieldStorageConfig::create([
      'field_name' => 'test_field',
      'entity_type' => 'node',
      'type' => 'string',
    ])->save();

    FieldConfig::create([
      'field_name' => 'test_field',
      'label' => 'Test Field',
      'entity_type' => 'node',
      'bundle' => 'page',
      'required' => FALSE,
      'settings' => [],
      'description' => '',
      ])->save();

    /** @var \Drupal\Core\Entity\Display\EntityFormDisplayInterface $entity_form_display */
    $entity_form_display = \Drupal::entityTypeManager()
      ->getStorage('entity_form_display')
      ->load('node.page.default');
    $entity_form_display->setComponent('test_field')->save();

    /** @var \Drupal\Core\Entity\Display\EntityViewDisplayInterface $entity_display */
    $entity_display = \Drupal::entityTypeManager()
      ->getStorage('entity_view_display')
      ->load('node.page.default');
    $entity_display->setComponent('test_field')->save();

    // Create a privileged user.
    $user = $this->drupalCreateUser([
      'access contextual links',
      'access in-place editing',
      'access content',
      'administer node display',
      'administer panelizer',
      'create page content',
      'edit any page content',
    ]);
    $this->drupalLogin($user);

    // Enable Panelizer for Articles.
    $this->drupalGet('admin/structure/types/manage/page/display');
    $this->assertResponse(200);
    $edit = [
      'panelizer[enable]' => TRUE,
    ];
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertResponse(200);
  }

  /**
   * Tests Quick Editing a Panelized Node.
   */
  public function testPanelizerQuickEdit() {
    /** @var \Drupal\panelizer\PanelizerInterface $panelizer */
    $panelizer = \Drupal::service('panelizer');
    $displays = $panelizer->getDefaultPanelsDisplays('node', 'page', 'default');
    $display = $displays['default'];

    // Find the "test_field" block.
    $block_id = FALSE;
    foreach ($display->getConfiguration()['blocks'] as $block) {
      if ($block['id'] === 'entity_field:node:test_field') {
        $block_id = $block['uuid'];
      }
    }

    // Make sure we found a valid UUID.
    $this->assertNotFalse($block_id);

    // Create an Article.
    $node = $this->drupalCreateNode([
      'type' => 'page',
      'test_field' => [
        'value' => 'Change me',
      ],
    ]);

    // Visit the new node.
    $this->drupalGet('node/' . $node->id());
    $this->assertResponse(200);

    // This is the unique ID we append to normal Quick Edit field IDs.
    $panelizer_id = 'panelizer-full-block-id-' . $block_id;

    // Assemble common CSS selectors.
    $entity_selector = '[data-quickedit-entity-id="node/' . $node->id() . '"]';
    $field_selector = '[data-quickedit-field-id="node/' . $node->id() . '/test_field/' . $node->language()->getId() . '/' . $panelizer_id . '"]';

    // Wait until Quick Edit loads.
    $condition = "jQuery('" . $entity_selector . " .quickedit').length > 0";
    $this->assertJsCondition($condition, 10000);

    // Initiate Quick Editing.
    $this->triggerClick($entity_selector . ' [data-contextual-id] > button');
    $this->click($entity_selector . ' [data-contextual-id] .quickedit > a');
    $this->triggerClick($field_selector);
    $this->assertSession()->assertWaitOnAjaxRequest();

    // Trigger an edit with Javascript (this is a "contenteditable" element).
    $this->getSession()->executeScript("jQuery('" . $field_selector . "').text('Hello world').trigger('keyup');");

    // To prevent 403s on save, we re-set our request (cookie) state.
    $this->prepareRequest();

    // Save the change.
    $this->triggerClick('.quickedit-button.action-save');
    $this->assertSession()->assertWaitOnAjaxRequest();

    // Re-visit the node to make sure the edit worked.
    $this->drupalGet('node/' . $node->id());
    $this->assertResponse(200);
    $this->assertSession()->pageTextContains('Hello world');
  }

  /**
  * Clicks the element with the given CSS selector using event triggers.
  *
  * @todo Remove when https://github.com/jcalderonzumba/gastonjs/issues/19
  * is fixed. Currently clicking anchors/buttons with nested elements is not
  * possible.
  *
  * @param string $css_selector
  *   The CSS selector identifying the element to click.
  */
  protected function triggerClick($css_selector) {
    $this->getSession()->executeScript("jQuery('" . $css_selector . "')[0].click()");
  }

}
