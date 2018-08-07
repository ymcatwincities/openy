<?php

namespace Drupal\Tests\paragraphs\FunctionalJavascript;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Entity\Display\EntityFormDisplayInterface;
use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\field_ui\Tests\FieldUiTestTrait;
use Drupal\FunctionalJavascriptTests\JavascriptTestBase;
use Drupal\node\Entity\NodeType;
use Drupal\paragraphs\Entity\ParagraphsType;
use Drupal\paragraphs\Tests\Classic\ParagraphsCoreVersionUiTestTrait;
use Drupal\Tests\paragraphs\FunctionalJavascript\LoginAdminTrait;

/**
 * Test paragraphs user interface.
 *
 * @group paragraphs
 */
class ParagraphsExperimentalAddWidgetTest extends JavascriptTestBase {

  use LoginAdminTrait;
  use FieldUiTestTrait;
  use ParagraphsTestBaseTrait;
  use ParagraphsCoreVersionUiTestTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'node',
    'paragraphs_test',
    'paragraphs',
    'field',
    'field_ui',
    'block',
    'link',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp()
  {
    parent::setUp();
    // Place the breadcrumb, tested in fieldUIAddNewField().
    $this->drupalPlaceBlock('system_breadcrumb_block');
    $this->drupalPlaceBlock('local_tasks_block');
    $this->drupalPlaceBlock('local_actions_block');
    $this->drupalPlaceBlock('page_title_block');

  }

  /**
   * Tests the add widget button with modal form.
   */
  public function testAddWidgetButton() {
    $this->addParagraphedContentType('paragraphed_test');
    $this->loginAsAdmin([
      'administer content types',
      'administer node form display',
      'edit any paragraphed_test content',
      'create paragraphed_test content',
    ]);
    // Set the add mode on the content type to modal form widget.
    $this->drupalGet('admin/structure/types/manage/paragraphed_test/form-display');
    $page = $this->getSession()->getPage();
    $page->pressButton('field_paragraphs_settings_edit');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $edit = [
      'fields[field_paragraphs][settings_edit_form][settings][edit_mode]' => 'closed',
      'fields[field_paragraphs][settings_edit_form][settings][add_mode]' => 'modal'
    ];
    $this->drupalPostForm(NULL, $edit, 'Update');
    $this->assertSession()->assertWaitOnAjaxRequest();

    $this->drupalPostForm(NULL, [], t('Save'));

    // Add a Paragraph type.
    $paragraph_type = 'text_paragraph';
    $this->addParagraphsType($paragraph_type);
    $this->addParagraphsType('text');

    // Add icons to the paragraphs types.
    $icon_one = $this->addParagraphsTypeIcon($paragraph_type);
    $icon_two = $this->addParagraphsTypeIcon('text');

    // Add a text field to the text_paragraph type.
    static::fieldUIAddNewField('admin/structure/paragraphs_type/' . $paragraph_type, 'text', 'Text', 'text_long', [], []);

    // Create paragraph type Nested test.
    $this->addParagraphsType('nested_test');

    static::fieldUIAddNewField('admin/structure/paragraphs_type/nested_test', 'paragraphs', 'Paragraphs', 'entity_reference_revisions', [
      'settings[target_type]' => 'paragraph',
      'cardinality' => '-1',
    ], []);

    // Set the settings for the field in the nested paragraph.
    $component = [
      'type' => 'paragraphs',
      'region' => 'content',
      'settings' => [
        'edit_mode' => 'closed',
        'add_mode' => 'modal',
        'form_display_mode' => 'default',
      ],
    ];
    EntityFormDisplay::load('paragraph.nested_test.default')->setComponent('field_paragraphs', $component)->save();

    // Add a paragraphed test.
    $this->drupalGet('node/add/paragraphed_test');

    // Add a nested paragraph with the add widget.
    $page->pressButton('Add Paragraph');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->assertSession()->elementTextContains('css', '.ui-dialog-title', 'Add Paragraph');
    $page->pressButton('nested_test');
    $this->assertSession()->assertWaitOnAjaxRequest();

    // Verify that the paragraphs type icons are being displayed.
    $button_one = $this->assertSession()->buttonExists($paragraph_type);
    $button_two = $this->assertSession()->buttonExists('text');
    $this->assertContains($icon_one->getFilename(), $button_one->getAttribute('style'));
    $this->assertContains($icon_two->getFilename(), $button_two->getAttribute('style'));

    // Find the add button in the nested paragraph with xpath.
    $element = $this->xpath('//div[contains(@class, "form-wrapper")]/div[contains(@class, "paragraph-type-add-modal")]/input');
    $element[0]->click();
    $this->assertSession()->assertWaitOnAjaxRequest();

    // Add a text inside the nested paragraph.
    $page = $this->getSession()->getPage();
    $dialog = $page->find('xpath', '//div[contains(@class, "ui-dialog")]');
    $dialog->pressButton('text');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $edit = [
      'title[0][value]' => 'Example title',
    ];
    $this->drupalPostForm(NULL, $edit, t('Save'));


    // Check the created paragraphed test.
    $this->assertText('paragraphed_test Example title has been created.');
    $this->assertRaw('paragraph--type--nested-test');
    $this->assertRaw('paragraph--type--text');

    // Add a paragraphs field with another paragraphs widget title to the
    // paragraphed_test content type.
    $this->addParagraphsField('paragraphed_test', 'field_paragraphs_two', 'node');
    $settings = [
      'title' => 'Renamed paragraph',
      'title_plural' => 'Renamed paragraphs',
      'add_mode' => 'modal',
    ];
    $this->setParagraphsWidgetSettings('paragraphed_test', 'field_paragraphs_two', $settings);

    // Check that the "add" buttons and modal form windows are labeled
    // correctly.
    $this->drupalGet('node/add/paragraphed_test');
    $page->pressButton('Add Paragraph');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->assertSession()->elementTextContains('css', '.ui-dialog-title', 'Add Paragraph');
    $this->assertSession()->elementTextNotContains('css', '.ui-dialog-title', 'Add Renamed paragraph');
    $this->assertSession()->elementExists('css', '.ui-dialog-titlebar-close')->press();
    $page->pressButton('Add Renamed paragraph');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->assertSession()->elementTextContains('css', '.ui-dialog-title', 'Add Renamed paragraph');
    $this->assertSession()->elementTextNotContains('css', '.ui-dialog-title', 'Add Paragraph');
  }
}
